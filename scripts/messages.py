from tqdm import tqdm
from vk_api import VkApi

community_token = "5f9071c17793443349971f3dc00ac71fa16d25e1c81cd39e8cb6447075499627dc4c9108a0713785dc9bb"
vk = VkApi(token=community_token).get_api()

users = []
for offset in range(0, 10000000, 200):
    messages = vk.messages.getConversations(offset=offset, count=200)
    if not messages["items"]:
        break
    for item in messages["items"]:
        if item["conversation"]["peer"]["type"] == "user":
            users.append(item["conversation"]["peer"]["id"])

dates = []
for user_id in tqdm(users):
    dates.append(vk.messages.getHistory(offset=0, count=1, user_id=user_id)["items"][0]["date"])
print(dates)