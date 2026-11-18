import requests
import json
import time
import os

# GANTI dengan API Key Google Places Anda
API_KEY = os.environ.get("GOOGLE_PLACES_API_KEY", "YAIzaSyAZaEAos1NNU8oGJ-d2otTZJnoo_8afj9s")

CATEGORIES = [
    ("restaurant", "Food & Beverage"),
    ("cafe", "Food & Beverage"),
    ("clothing_store", "Fashion"),
    ("shoe_store", "Fashion"),
    ("electronics_store", "Electronics"),
    ("movie_theater", "Entertainment"),
    ("supermarket", "Supermarket"),
    ("book_store", "Books"),
    ("jewelry_store", "Accessories"),
    ("pharmacy", "Health"),
]

def get_mall_coordinates(mall_name, city, province):
    url = "https://maps.googleapis.com/maps/api/place/findplacefromtext/json"
    params = {
        "input": f"{mall_name}, {city}, {province}, Indonesia",
        "inputtype": "textquery",
        "fields": "place_id,name,geometry,formatted_address",
        "key": API_KEY
    }

    response = requests.get(url, params=params).json()

    if response.get("candidates"):
        c = response["candidates"][0]
        return {
            "place_id": c["place_id"],
            "name": c["name"],
            "address": c.get("formatted_address", ""),
            "lat": c["geometry"]["location"]["lat"],
            "lng": c["geometry"]["location"]["lng"]
        }
    return None

def search_stores_near_mall(mall_info, category_type, category_name, radius=400):
    url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json"
    params = {
        "location": f"{mall_info['lat']},{mall_info['lng']}",
        "radius": radius,
        "type": category_type,
        "key": API_KEY
    }

    response = requests.get(url, params=params).json()
    stores = []

    for place in response.get("results", []):
        stores.append({
            "id": place["place_id"],
            "name": place["name"],
            "category": category_name,
            "category_type": category_type,
            "address": place.get("vicinity", ""),
            "lat": place["geometry"]["location"]["lat"],
            "lng": place["geometry"]["location"]["lng"],
            "rating": place.get("rating", 0),
            "user_ratings_total": place.get("user_ratings_total", 0),
            "business_status": place.get("business_status", "OPERATIONAL"),
            "price_level": place.get("price_level", 0),
            "mall_name": mall_info["name"],
            "city": mall_info["city"],
            "province": mall_info["province"]
        })

    return stores

def get_place_details(place_id):
    url = "https://maps.googleapis.com/maps/api/place/details/json"
    params = {
        "place_id": place_id,
        "fields": "name,opening_hours,website,phone,price_level",
        "key": API_KEY
    }

    response = requests.get(url, params=params).json()
    result = response.get("result", {})

    opening_hours = {}
    if "opening_hours" in result and "weekday_text" in result["opening_hours"]:
        for day_text in result["opening_hours"]["weekday_text"]:
            parts = day_text.split(": ", 1)
            if len(parts) == 2:
                opening_hours[parts[0].lower()] = parts[1]

    return {
        "opening_hours": opening_hours,
        "website": result.get("website", ""),
        "phone": result.get("formatted_phone_number", ""),
        "price_level": result.get("price_level", 0)
    }

def scrape_all_stores_for_mall(mall_info):
    all_stores = []
    print(f"\nScraping: {mall_info['name']} ({mall_info['city']})")

    for cat_type, cat_name in CATEGORIES:
        print(f"  Kategori: {cat_name}...")
        stores = search_stores_near_mall(mall_info, cat_type, cat_name)

        for store in stores:
            time.sleep(0.2)
            details = get_place_details(store["id"])
            store.update(details)
            all_stores.append(store)

        time.sleep(0.5)

    print(f"  Total: {len(all_stores)} toko")
    return all_stores

def main():
    with open("malls_sumatera.json", "r", encoding="utf-8") as f:
        malls = json.load(f)

    all_stores = []

    for i, mall in enumerate(malls):
        print(f"\n[{i+1}/{len(malls)}] Processing {mall['name']}")

        mall_info = get_mall_coordinates(mall["name"], mall["city"], mall["province"])
        if not mall_info:
            print(f"  Mall tidak ditemukan, skip")
            continue

        mall_info["province"] = mall["province"]
        mall_info["city"] = mall["city"]
        mall_info["description"] = mall.get("description", "")

        stores = scrape_all_stores_for_mall(mall_info)
        all_stores.extend(stores)

        if (i + 1) % 5 == 0:
            with open("stores_progress.json", "w", encoding="utf-8") as f:
                json.dump(all_stores, f, ensure_ascii=False, indent=2)

        time.sleep(1)

    with open("stores_sumatera.json", "w", encoding="utf-8") as f:
        json.dump(all_stores, f, ensure_ascii=False, indent=2)

    print(f"\nTotal {len(all_stores)} toko berhasil di-scrape!")

if __name__ == "__main__":
    main()