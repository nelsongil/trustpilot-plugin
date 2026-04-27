#!/usr/bin/env python3
"""
Fetches Trustpilot reviews and saves them as reviews.json.
Runs inside GitHub Actions — GitHub's IPs are not blocked by Trustpilot/Cloudflare.
"""

import json
import os
import re
import sys
from datetime import datetime, timezone

import requests

TRUSTPILOT_URL = os.environ.get("TRUSTPILOT_URL", "").strip()

if not TRUSTPILOT_URL:
    print("ERROR: TRUSTPILOT_URL secret not set. Add it in Settings → Secrets → Actions.")
    sys.exit(1)

HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) "
        "Chrome/124.0.0.0 Safari/537.36"
    ),
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
    "Accept-Language": "es-ES,es;q=0.9,en-US;q=0.8,en;q=0.7",
    "Accept-Encoding": "gzip, deflate, br",
    "Cache-Control": "max-age=0",
    "Sec-Fetch-Dest": "document",
    "Sec-Fetch-Mode": "navigate",
    "Sec-Fetch-Site": "none",
    "Sec-Fetch-User": "?1",
    "Upgrade-Insecure-Requests": "1",
}

def fetch_page(url):
    print(f"Fetching: {url}")
    resp = requests.get(url, headers=HEADERS, timeout=30, allow_redirects=True)
    print(f"Status: {resp.status_code}")
    if resp.status_code != 200:
        print(f"ERROR: HTTP {resp.status_code}")
        sys.exit(1)
    return resp.text

def extract_next_data(html):
    match = re.search(r'<script[^>]+id="__NEXT_DATA__"[^>]*>(.*?)</script>', html, re.DOTALL)
    if not match:
        print("ERROR: __NEXT_DATA__ not found in page.")
        sys.exit(1)
    return json.loads(match.group(1))

def find_reviews_in_tree(node, depth=0):
    """Recursively find the first list that looks like Trustpilot reviews."""
    if depth > 8 or not isinstance(node, (dict, list)):
        return None

    if isinstance(node, list) and len(node) >= 1 and isinstance(node[0], dict):
        first = node[0]
        review_keys = {"rating", "stars", "text", "title", "consumer", "author"}
        if len(review_keys & set(first.keys())) >= 2:
            return node

    children = node.values() if isinstance(node, dict) else node
    for child in children:
        found = find_reviews_in_tree(child, depth + 1)
        if found is not None:
            return found
    return None

def parse_reviews(data, base_url):
    # Try known paths first
    candidate_paths = [
        ["props", "pageProps", "reviews"],
        ["props", "pageProps", "businessUnit", "reviews"],
        ["props", "pageProps", "reviewsState", "reviews"],
    ]

    reviews_raw = None
    for path in candidate_paths:
        node = data
        ok = True
        for key in path:
            if isinstance(node, dict) and key in node:
                node = node[key]
            else:
                ok = False
                break
        if ok and isinstance(node, list) and node:
            reviews_raw = node
            print(f"Found reviews at path: {' > '.join(path)}")
            break

    if reviews_raw is None:
        print("Known paths failed, walking tree...")
        reviews_raw = find_reviews_in_tree(data)

    if not reviews_raw:
        print("ERROR: No reviews found in __NEXT_DATA__.")
        sys.exit(1)

    reviews = []
    for r in reviews_raw:
        if not isinstance(r, dict):
            continue

        title   = r.get("title", "").strip()
        content = r.get("text", r.get("content", "")).strip()
        rating  = int(r.get("rating", r.get("stars", 0)))
        author  = (
            (r.get("consumer") or {}).get("displayName")
            or (r.get("author") or {}).get("displayName")
            or ""
        )
        date = (
            (r.get("dates") or {}).get("publishedDate")
            or r.get("createdAt")
            or r.get("date")
            or ""
        )
        review_id  = r.get("id", "")
        review_url = f"{base_url.rstrip('/')}#{review_id}" if review_id else base_url

        if not content and not title:
            continue

        reviews.append({
            "title":      title or "Reseña sin título",
            "content":    content or "Reseña sin contenido",
            "consumer":   {"displayName": author or "Cliente Anónimo"},
            "rating":     rating if 1 <= rating <= 5 else 0,
            "review_url": review_url,
            "date":       date if isinstance(date, str) else "",
        })

    print(f"Parsed {len(reviews)} reviews.")
    return reviews

def main():
    html    = fetch_page(TRUSTPILOT_URL)
    data    = extract_next_data(html)
    reviews = parse_reviews(data, TRUSTPILOT_URL)

    output = {
        "fetched_at": datetime.now(timezone.utc).isoformat(),
        "source_url": TRUSTPILOT_URL,
        "count":      len(reviews),
        "reviews":    reviews,
    }

    output_path = os.path.join(os.path.dirname(__file__), "..", "..", "reviews.json")
    output_path = os.path.normpath(output_path)

    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(output, f, ensure_ascii=False, indent=2)

    print(f"Saved {len(reviews)} reviews to {output_path}")

if __name__ == "__main__":
    main()
