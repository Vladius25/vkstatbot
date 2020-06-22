#!/usr/bin/env python
"""
Скрипт предназачен для заполнения PostgreSQL
данными о первом сообщении в каждой переписке сообщества в вк
"""
import sys
from itertools import count

import psycopg2
from tqdm import tqdm
from vk_api import VkApi


def collect_conversations(vk):
    conversations = []
    for offset in count(start=0, step=200):
        messages = vk.messages.getConversations(offset=offset, count=200)
        if not messages["items"]:
            break
        for item in messages["items"]:
            if item["conversation"]["peer"]["type"] == "user":
                conversations.append(item["conversation"]["peer"]["id"])
    return conversations


def create_bd_table(cursor):
    cursor.execute('DROP TABLE IF EXISTS first_msg;')
    cursor.execute("CREATE TABLE first_msg (id serial PRIMARY KEY, group_id int NOT NULL, user_id int NOT NULL, "
                   "date timestamp, unique(group_id, user_id));")


def get_first_msg_date(vk, user_id):
    history = vk.messages.getHistory(count=200, user_id=user_id)["items"]
    for message in history:
        if message["from_id"] > 0:
            return message["date"]
    return None


def insert_data_in_bd(vk, cursor, converstaions, group_id):
    for user_id in tqdm(converstaions):
        if date := get_first_msg_date(vk, user_id):
            cursor.execute("INSERT INTO first_msg (group_id, user_id, date) VALUES (%s, %s, to_timestamp(%s))",
                           (group_id, user_id, date))


def main():
    if len(sys.argv) < 3:
        print("Нужно указать id группы и ее токен:")
        print("python messages.py <group_id> <access_token>")
        exit(1)
    group_id, community_token = sys.argv[1], sys.argv[2]
    vk = VkApi(token=community_token).get_api()

    conn = psycopg2.connect(user="postgres", password="developing", host="127.0.0.1", port=5432, database="vkstatbot")
    cursor = conn.cursor()
    create_bd_table(cursor)

    conversations = collect_conversations(vk)
    insert_data_in_bd(vk, cursor, conversations, group_id)

    conn.commit()
    conn.close()


if __name__ == "__main__":
    main()
