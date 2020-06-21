"""
Скрипт предназачен для заполнения PostgreSQL
данными о первом сообщении в каждой переписке сообщества в вк
"""
import itertools

import psycopg2
from tqdm import tqdm
from vk_api import VkApi

community_token = "5f9071c17793443349971f3dc00ac71fa16d25e1c81cd39e8cb6447075499627dc4c9108a0713785dc9bb"
vk = VkApi(token=community_token).get_api()

users = []
for offset in itertools.count(start=1, step=200):
    messages = vk.messages.getConversations(offset=offset, count=200)
    if not messages["items"]:
        break
    for item in messages["items"]:
        if item["conversation"]["peer"]["type"] == "user":
            users.append(item["conversation"]["peer"]["id"])


conn = psycopg2.connect(user="postgres", password="developing", host="127.0.0.1", port=5432, database="vkstatbot")
cursor = conn.cursor()
try:
    cursor.execute("CREATE TABLE first_msg (id serial PRIMARY KEY, user_id integer, date timestamp);")
except psycopg2.errors.DuplicateTable:
    cursor.execute('ROLLBACK;')
    cursor.execute('TRUNCATE first_msg;')

for user_id in tqdm(users):
    date = vk.messages.getHistory(offset=0, count=1, user_id=user_id)["items"][0]["date"]
    cursor.execute("INSERT INTO first_msg (user_id, date) VALUES (%s, to_timestamp(%s))", (user_id, date))

conn.commit()
conn.close()
