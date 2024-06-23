
base_url = 'http://chatrag.42web.io/wp-json/wp/v2'
import requests
from requests.exceptions import RequestException
import time

def fetch_posts(url):
    try:
        response = requests.get(url)
        response.raise_for_status()  # Raise HTTPError for bad responses
        return response.json()
    except RequestException as e:
        print(f'Error fetching posts: {e}')
        return None


endpoint = '/posts'
url = f'{base_url}{endpoint}'

retry_attempts = 3
for attempt in range(retry_attempts):
    posts = fetch_posts(url)
    if posts is not None:
        break
    time.sleep(1)  # Wait for 1 second before retrying

if posts:
    for post in posts:
        title = post['title']['rendered']
        content = post['content']['rendered']
        print(f'Title: {title}\nContent: {content}\n')
else:
    print('Failed to fetch posts after retrying.')
