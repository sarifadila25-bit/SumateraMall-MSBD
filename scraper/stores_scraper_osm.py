"""
Scraper toko menggunakan OpenStreetMap (GRATIS, tanpa API key)
Versi diperbaiki dengan error handling
"""

import requests
import json
import time
import re
from pathlib import Path

SCRIPT_DIR = Path(__file__).resolve().parent
HEADERS = {"User-Agent": "SumateraMallSemanticExplorer/1.0"}

def slugify(text):
    text = text.lower().strip()
    text = re.sub(r'[^\w\s-]', '', text)
    text = re.sub(r'[\s_-]+', '_', text)
    return text

def geocode_location(query):
    """Cari koordinat menggunakan Nominatim"""
    url = "https://nominatim.openstreetmap.org/search"
    params = {
        "q": query,
        "format": "json",
        "limit": 1
    }

    try:
        response = requests.get(url, params=params, headers=HEADERS, timeout=10)
        response.raise_for_status()
        results = response.json()

        if results:
            return {
                "lat": float(results[0]["lat"]),
                "lng": float(results[0]["lon"]),
                "display_name": results[0]["display_name"]
            }
    except (requests.RequestException, json.JSONDecodeError, KeyError, ValueError) as e:
        print(f"  Error geocoding: {e}")

    return None

def search_nearby_stores(lat, lng, radius_km=1):
    """
    Cari toko di sekitar koordinat menggunakan Overpass API
    Dengan error handling yang proper
    """
    overpass_url = "https://overpass-api.de/api/interpreter"
    radius_meters = radius_km * 1000

    # Query yang lebih sederhana dan fokus
    query = f"""
    [out:json][timeout:30];
    (
        node["shop"](around:{radius_meters},{lat},{lng});
        node["amenity"="restaurant"](around:{radius_meters},{lat},{lng});
        node["amenity"="cafe"](around:{radius_meters},{lat},{lng});
    );
    out body 50;
    """

    try:
        response = requests.post(
            overpass_url,
            data={"data": query},
            headers=HEADERS,
            timeout=30
        )

        # Cek status code
        if response.status_code != 200:
            print(f"  Overpass API error: {response.status_code}")
            return []

        # Cek apakah response valid JSON
        try:
            data = response.json()
        except json.JSONDecodeError:
            print(f"  Invalid JSON response: {response.text[:100]}")
            return []

        stores = []
        for element in data.get("elements", []):
            tags = element.get("tags", {})
            name = tags.get("name", tags.get("brand", "Unknown"))

            if name == "Unknown":
                continue

            # Kategorisasi sederhana
            shop_type = tags.get("shop", "")
            amenity = tags.get("amenity", "")

            if amenity in ["restaurant", "cafe", "fast_food"]:
                category = "Food & Beverage"
            elif shop_type in ["clothes", "shoes", "bag", "jewelry", "fashion"]:
                category = "Fashion"
            elif shop_type in ["electronics", "mobile_phone", "computer"]:
                category = "Electronics"
            elif shop_type in ["supermarket", "convenience"]:
                category = "Supermarket"
            elif shop_type in ["pharmacy", "chemist"]:
                category = "Health"
            elif shop_type in ["books", "stationery"]:
                category = "Books"
            elif shop_type in ["cinema", "theatre"]:
                category = "Entertainment"
            else:
                category = shop_type if shop_type else "Other"

            stores.append({
                "id": f"osm_{element['id']}",
                "name": name,
                "category": category,
                "category_type": shop_type or amenity,
                "address": tags.get("addr:street", ""),
                "lat": element["lat"],
                "lng": element["lon"],
                "rating": 0,
                "user_ratings_total": 0,
                "business_status": "OPERATIONAL",
                "price_level": 0,
                "opening_hours": tags.get("opening_hours", ""),
                "website": tags.get("website", ""),
                "phone": tags.get("phone", ""),
            })

        return stores[:20]  # Batasi 20 toko per mall

    except requests.exceptions.Timeout:
        print("  Timeout: Overpass API tidak merespon")
        return []
    except requests.exceptions.ConnectionError:
        print("  Connection error: Cek koneksi internet")
        return []
    except Exception as e:
        print(f"  Unexpected error: {e}")
        return []

def main():
    with (SCRIPT_DIR / "malls_sumatera.json").open("r", encoding="utf-8") as f:
        malls = json.load(f)

    all_stores = []

    for i, mall in enumerate(malls):
        print(f"\n[{i+1}/{len(malls)}] Processing {mall['name']}")

        # Geocode mall
        query = f"{mall['name']}, {mall['city']}, {mall['province']}, Indonesia"
        mall_coords = geocode_location(query)

        if not mall_coords:
            print(f"  Mall tidak ditemukan, skip")
            continue

        mall["lat"] = mall_coords["lat"]
        mall["lng"] = mall_coords["lng"]
        mall["address"] = mall_coords["display_name"]
        print(f"  Koordinat: {mall_coords['lat']}, {mall_coords['lng']}")

        # Delay untuk rate limit Nominatim
        time.sleep(2)

        # Cari toko di sekitar mall (radius 1km)
        stores = search_nearby_stores(mall_coords['lat'], mall_coords['lng'], radius_km=1)

        for store in stores:
            store["mall_name"] = mall["name"]
            store["city"] = mall["city"]
            store["province"] = mall["province"]

        all_stores.extend(stores)
        print(f"  Ditemukan {len(stores)} toko")

        # Backup progress setiap 5 mall
        if (i + 1) % 5 == 0:
            with (SCRIPT_DIR / "stores_progress.json").open("w", encoding="utf-8") as f:
                json.dump(all_stores, f, ensure_ascii=False, indent=2)
            print(f"  Progress saved: {len(all_stores)} total stores")

        # Delay untuk rate limit Overpass
        time.sleep(3)

    # Simpan final
    with (SCRIPT_DIR / "stores_sumatera.json").open("w", encoding="utf-8") as f:
        json.dump(all_stores, f, ensure_ascii=False, indent=2)

    with (SCRIPT_DIR / "malls_sumatera.json").open("w", encoding="utf-8") as f:
        json.dump(malls, f, ensure_ascii=False, indent=2)
    print(f"\n{'='*50}")
    print(f"Total {len(all_stores)} toko berhasil di-scrape!")
    print(f"{'='*50}")

if __name__ == "__main__":
    main()
