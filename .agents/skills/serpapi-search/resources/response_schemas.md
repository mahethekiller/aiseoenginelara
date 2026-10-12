# SerpApi Google Search API - Response Schemas & Parsing Guide

Comprehensive structural breakdown of JSON response modules returned by `https://serpapi.com/search.json`.

---

## 1. Top-Level Response Wrapper

```json
{
  "search_metadata": {
    "id": "673abc1234def5678",
    "status": "Success",
    "json_endpoint": "https://serpapi.com/searches/abc/673abc1234def5678.json",
    "created_at": "2026-10-12 05:40:00 UTC",
    "processed_at": "2026-10-12 05:40:01 UTC",
    "google_url": "https://www.google.com/search?q=emergency+hospital&uule=...",
    "raw_html_file": "https://serpapi.com/searches/abc/673abc1234def5678.html",
    "total_time_taken": 1.45
  },
  "search_parameters": {
    "engine": "google",
    "q": "emergency hospital",
    "location_requested": "Ahmedabad, Gujarat, India",
    "location_used": "Ahmedabad, Gujarat, India",
    "google_domain": "google.co.in",
    "hl": "en",
    "gl": "in",
    "device": "desktop"
  },
  "search_information": {
    "organic_results_state": "Results for exact spelling",
    "query_displayed": "emergency hospital",
    "total_results": 245000000,
    "time_taken_displayed": 0.42,
    "spelling_fix": null
  }
}
```

---

## 2. Organic Results (`organic_results`)

An array of regular search engine page listings.

```json
[
  {
    "position": 1,
    "title": "Emergency Medicine Services - Sterling Hospitals",
    "link": "https://www.sterlinghospitals.com/specialities/emergency-medicine",
    "displayed_link": "https://www.sterlinghospitals.com › specialities › emergency-medicine",
    "snippet": "Sterling Hospitals provides 24x7 emergency medical trauma care in Ahmedabad...",
    "snippet_highlighted_words": [
      "Emergency",
      "Medicine",
      "Ahmedabad"
    ],
    "sitelinks": {
      "inline": [
        { "title": "Contact Us", "link": "https://www.sterlinghospitals.com/contact" },
        { "title": "Find a Doctor", "link": "https://www.sterlinghospitals.com/doctors" }
      ]
    },
    "rich_snippet": {
      "bottom": {
        "extensions": ["Open 24 hours", "Multi-speciality trauma center"]
      }
    },
    "about_this_result": {
      "source": {
        "description": "sterlinghospitals.com was first indexed by Google more than 10 years ago",
        "source_info_link": "https://www.google.com/search?q=about:https://www.sterlinghospitals.com",
        "security": "secure"
      }
    }
  }
]
```

### Parsing Logic
```php
foreach ($data['organic_results'] ?? [] as $index => $result) {
    $rank = $result['position'] ?? ($index + 1);
    $url = $result['link'] ?? '';
    $title = $result['title'] ?? '';
    $snippet = $result['snippet'] ?? '';
    $domain = parse_url($url, PHP_URL_HOST);
}
```

---

## 3. Local Results & Map Pack (`local_results`)

Returned when queries possess local geographic intent (e.g. "hospital near me", "best dentists in Chicago").

```json
{
  "local_results": {
    "more_locations_link": "https://www.google.com/search?tbm=lcl&q=...",
    "places": [
      {
        "position": 1,
        "title": "Sterling Hospital Ahmedabad",
        "place_id": "11265938073076301333",
        "lsig": "AB86z5Vdw6C2pJpM0xQ6JUx2KONU",
        "rating": 4.6,
        "reviews": 1250,
        "price": "$$",
        "type": "Emergency room",
        "address": "Sterling Hospital Rd, Memnagar, Ahmedabad, Gujarat 380052",
        "phone": "+91 79 4001 1111",
        "hours": "Open 24 hours",
        "gps_coordinates": {
          "latitude": 23.05124,
          "longitude": 72.53128
        },
        "links": {
          "website": "https://www.sterlinghospitals.com/",
          "directions": "https://www.google.com/maps/dir//Sterling+Hospital..."
        },
        "thumbnail": "https://lh5.googleusercontent.com/p/AF1QipN..."
      }
    ]
  }
}
```

---

## 4. Featured Snippet / Answer Box (`answer_box`)

Direct answers displayed prominently in Position 0.

```json
{
  "answer_box": {
    "type": "organic_result",
    "title": "What is Emergency Medicine?",
    "link": "https://en.wikipedia.org/wiki/Emergency_medicine",
    "displayed_link": "https://en.wikipedia.org › wiki › Emergency_medicine",
    "snippet": "Emergency medicine is the medical specialty concerned with the care of illnesses or injuries requiring immediate medical attention...",
    "snippet_highlighted_words": ["Emergency medicine", "immediate medical attention"],
    "list": [
      "Trauma resuscitation",
      "Advanced cardiac life support",
      "Pediatric emergency triage"
    ]
  }
}
```

---

## 5. Knowledge Graph (`knowledge_graph`)

Entity summary card displayed in the right sidebar.

```json
{
  "knowledge_graph": {
    "title": "Sterling Hospitals",
    "type": "Hospital company in India",
    "description": "Sterling Hospitals is a chain of multi-speciality tertiary care hospitals headquartered in Ahmedabad, Gujarat, India.",
    "source": {
      "name": "Wikipedia",
      "link": "https://en.wikipedia.org/wiki/Sterling_Hospitals"
    },
    "attributes": {
      "headquarters": "Ahmedabad, Gujarat",
      "founded": "2001",
      "founders": "Girish Patel"
    },
    "profiles": [
      { "name": "LinkedIn", "link": "https://www.linkedin.com/company/sterling-hospitals" },
      { "name": "Facebook", "link": "https://www.facebook.com/sterlinghospitals" }
    ]
  }
}
```

---

## 6. People Also Ask (`related_questions`)

Accordion question widgets inserted throughout organic results.

```json
[
  {
    "question": "Which is the best hospital for emergency in Ahmedabad?",
    "snippet": "Sterling Hospitals and Apollo Hospitals are widely rated as premier tertiary emergency facilities in Ahmedabad with 24x7 trauma teams.",
    "title": "Top Emergency Trauma Hospitals in Ahmedabad",
    "link": "https://www.example.com/top-emergency-hospitals",
    "displayed_link": "https://www.example.com › top-emergency-hospitals"
  }
]
```

---

## 7. Discussions and Forums (`discussions_and_forums`)

Community threads (e.g. Reddit, Quora, specialized forums) highlighted by Google.

```json
[
  {
    "title": "Recommendations for best emergency hospitals in West Ahmedabad?",
    "link": "https://www.reddit.com/r/ahmedabad/comments/12345/emergency_hospitals/",
    "source": "Reddit · r/ahmedabad",
    "answers": 42,
    "date": "2 months ago"
  }
]
```

---

## 8. Pagination Metadata (`pagination`)

Tokens and endpoints to traverse subsequent SERP result pages.

```json
{
  "pagination": {
    "current": 1,
    "next": "https://serpapi.com/search.json?engine=google&gl=in&google_domain=google.co.in&hl=en&location=Ahmedabad%2C+Gujarat%2C+India&q=emergency+hospital&start=10",
    "other_pages": {
      "2": "https://serpapi.com/search.json?...start=10",
      "3": "https://serpapi.com/search.json?...start=20",
      "4": "https://serpapi.com/search.json?...start=30"
    }
  }
}
```
