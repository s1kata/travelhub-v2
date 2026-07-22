#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json
import re
import urllib.parse
import urllib.request
from datetime import date, timedelta
from pathlib import Path

BASE = "https://travelhub63.ru/frontend/api/tourvisor-proxy.php"
DEP = 7
COUNTRY = 16
OUT = Path(__file__).resolve().parents[1] / "data" / "phuquoc_promo_check.json"


def fetch(params: dict) -> dict:
    url = BASE + "?" + urllib.parse.urlencode(params)
    with urllib.request.urlopen(url, timeout=180) as r:
        return json.loads(r.read().decode("utf-8"))


def main() -> None:
    regs = fetch({"type": "regions", "countryId": str(COUNTRY)})
    phu_ids = []
    for row in regs.get("data") or []:
        name = row.get("name") or ""
        rid = int(row.get("id") or 0)
        if re.search(r"фук|phu\s*quoc|phuquoc", name, re.I):
            phu_ids.append(rid)

    df = (date.today() + timedelta(days=7)).isoformat()
    dt = (date.today() + timedelta(days=60)).isoformat()
    promo = fetch(
        {
            "type": "promo-search",
            "departureId": str(DEP),
            "countryId": str(COUNTRY),
            "dateFrom": df,
            "dateTo": dt,
            "adults": "2",
            "live": "1",
        }
    )
    hotels = promo.get("data") or []

    by_region = {}
    for h in hotels:
        reg = h.get("region") or {}
        if isinstance(reg, dict):
            rid = reg.get("id")
            rname = reg.get("name") or reg.get("russianName") or ""
        else:
            rid = h.get("regionId")
            rname = str(reg or "")
        key = (rid, rname)
        by_region.setdefault(key, 0)
        by_region[key] += 1

    if not phu_ids:
        for (rid, rname), _ in by_region.items():
            if rid and re.search(r"фук|phu\s*quoc|phuquoc", rname or "", re.I):
                phu_ids.append(int(rid))

    out = {
        "regions": regs.get("data") or [],
        "promo_total": len(hotels),
        "promo_success": bool(promo.get("success")),
        "by_region": {f"{rid}|{rname}": cnt for (rid, rname), cnt in by_region.items()},
        "phu_quoc_region_ids": phu_ids,
        "region_checks": {},
    }

    for rid in phu_ids:
        region_promo = fetch(
            {
                "type": "promo-search",
                "departureId": str(DEP),
                "countryId": str(COUNTRY),
                "regionIds": str(rid),
                "dateFrom": df,
                "dateTo": dt,
                "adults": "2",
                "live": "1",
            }
        )
        rh = region_promo.get("data") or []
        out["region_checks"][str(rid)] = {
            "success": bool(region_promo.get("success")),
            "count": len(rh),
            "sample": [
                {
                    "name": h.get("name"),
                    "region": (h.get("region") or {}).get("name")
                    if isinstance(h.get("region"), dict)
                    else h.get("region"),
                }
                for h in rh[:5]
            ],
        }

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(out, ensure_ascii=False, indent=2), encoding="utf-8")
    print(str(OUT))


if __name__ == "__main__":
    main()
