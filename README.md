# Restaurants

## API Documentation

### Endpoints

#### Get All Restaurants

`GET /api/restaurants`

**Description:** Retrieves a list of restaurants.

**Query Parameters:**
- **`page`** (optional): The page number to retrieve. Default is '1'.
- **`status`** (optional): Whether to retrieve only 'open', only 'closed' or 'all' restaurants. Default is 'all'.
- **`name`** (optional): The name to filter by.
- **`cuisine`** (optional): The cuisine to filter by.

**Example:**
```bash
curl -L 'http://localhost/api/restaurants?page=1&status=open' \
--header 'Accept: application/json'
```

#### Get Restaurant by ID

`GET /api/restaurants/:id`

**Description:** Retrieves a restaurant by its ID.

**Example:**
```bash
curl -L 'http://localhost/api/restaurants/1' \
--header 'Accept: application/json'
```

#### Import Restaurants via .csv file

`POST /api/restaurants/import`

**Description:** Imports a list of restaurants from a .csv file.

**Form Data:**


| Key  | Value | Description |
| --- | --- | --- |
| file  | File | The .csv file containing the restaurants to import. |

**CURL example:**
```bash
curl -L 'http://localhost/api/restaurants/import' \
--header 'Accept: application/json' \
--form 'file=@"/path/to/restaurants.csv"'
