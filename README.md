![](https://www.teguharief.com/img/teguh-arief.png)

# Headless CMS

a headless CMS is a back-end-only content management system that provides a way to manage content. It used Laravel 8 and MySQL with JWT Auth.

## Installation

Install the app on terminal

```
git clone https://github.com/teguharifudin/Headless-CMS.git
```
```
cd Headless-CMS
```
```
composer install
```
```
cp .env.example .env
```
```
php artisan key:generate
```
```
php artisan migrate
```
```
php artisan serve
```

## API Documentation

Endpoints, required parameters, request/response formats, and example payloads.

### HTTP requests

API using standard GET, POST, PUT, and DELETE methods.

### Responses

a JSON response in the following format:

```
{
  "success" : bool,
  "message" : string,
  "data"    : string
}
```

The `success` attribute describes if the transaction was successful or not.

The `message` attribute contains a message commonly used to indicate errors or, in the case of deleting a resource, success that the resource was properly deleted.

The `data` attribute contains any other metadata associated with the response. This will be an escaped string containing JSON data.

### Status Codes

the following status codes in its API:

| Status Code | Description |
| :--- | :--- |
| 200 | `OK` |
| 201 | `CREATED` |
| 404 | `NOT FOUND` |
| 422 | `UNPROCESSABLE ENTITY` |
| 500 | `INTERNAL SERVER ERROR` |

### Authorization payload

API Endpoint Authentication and Security Authentication is implemented using JWTs (JSON Web Tokens) with payload.

### Authorization

#### Register

```
POST /api/auth/register
```

```
{
    "name": "Teguh Arief",
    "email": "teguh@arief.com",
    "password": "admin123",
    "password_confirmation": "admin123"
}
```

#### Login

```
GET /api/auth/login
```

```
{
    "email": "teguh@arief.com",
    "password": "admin123"
}
```

```
// Get JWT Token from response:

{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE3MzgzNjQxMTYsImV4cCI6MTczODM2NzcxNiwibmJmIjoxNzM4MzY0MTE2LCJqdGkiOiJSNFFSVFdiUlg0WGZ1NHhCIiwic3ViIjoiMSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.weoFLH7c-9Ka1sBFml17Eow03LGHSRMTS_w5EuCvEec",
    "token_type": "bearer",
    "expires_in": 3600
}
```

#### Me

```
GET Bearer Token /api/auth/me
```

```
// Status 200 response:

{
    "id": 1,
    "name": "Teguh Arief",
    "email": "teguh@arief.com",
    "email_verified_at": null,
    "created_at": "2025-01-31T22:51:49.000000Z",
    "updated_at": "2025-01-31T22:51:49.000000Z"
}
```

#### Logout

```
POST Bearer Token /api/auth/logout
```

### Media

#### Store

```
POST Bearer Token /api/media
```

```
Body (form-data):
KEY         TYPE    VALUE
file        File    [Select your file]
name        Text    My Profile Picture
```

#### Retrieve images and videos

```
GET Bearer Token /api/media

GET Bearer Token /api/media/{id}
```

#### Delete

```
DELETE Bearer Token /api/media/{id}
```

### Pages

#### Create

```
POST Bearer Token /api/pages
```

```
{
    "title": "My First Page",
    "content": "<h1>Welcome to my page</h1><p>This is the content.</p>",
    "banner_media_id": null,
    "published_at": "2025-02-15 14:30:00",
    "status": "draft"
}
```

```
// Status 201 response:

{
    "success": true,
    "message": "Page created successfully",
    "data": {
        "title": "My First Page",
        "content": "<h1>Welcome to my page</h1><p>This is the content.</p>",
        "banner_media_id": null,
        "status": "draft",
        "published_at": "2025-02-15 14:30:00",
        "slug": "my-first-page",
        "author_id": 1,
        "updated_at": "2025-01-31T23:08:24.000000Z",
        "created_at": "2025-01-31T23:08:24.000000Z",
        "id": 1
    }
}
```

```
// Status 422 response:

{
    "success": false,
    "message": {
        "title": [
            "The title field is required."
        ]
    },
    "data": null
}
```

#### Read

```
GET Bearer Token /api/pages

GET Bearer Token /api/pages/{id}
```

#### Update

```
PUT Bearer Token /api/pages/{id}
```

```
{
    "banner_media_id": 1,
    "status": "published"
}
```

#### Delete

```
DELETE Bearer Token /api/pages/{id}
```

### Team Member

#### Create

```
POST Bearer Token /api/team-members
```

```
Body (form-data):
KEY                          TYPE    VALUE
name                         Text    John Doe
role                         Text    Senior Developer
bio                          Text    Lorem ipsum dolor sit amet, consectetur adipiscing elit.
email                        Text    john@doe.com
profile_picture              File    [Select your image file]
order                        Text    1
is_active                    Text    0
```

#### Read

```
GET Bearer Token /api/team-members

GET Bearer Token /api/team-members/{id}
```

#### Update

```
POST Bearer Token /api/team-members/{id}
```

```
Body (form-data):
KEY                          TYPE    VALUE
_method                      Text    PUT
name                         Text    John Doe
role                         Text    Senior Developer
bio                          Text    Lorem ipsum dolor sit amet, consectetur adipiscing elit.
email                        Text    john@doe.com
profile_picture              File    [Select your image file]
order                        Text    1
is_active                    Text    1
```

#### Delete

```
DELETE Bearer Token /api/team-members/{id}
```

## Usage: CI/CD deployments with GitHub Actions on hosted

For API endpoint usage, you can try using Postman.

### Authorization

```
POST https://palmcode.hidrogen.id/api/auth/register

POST https://palmcode.hidrogen.id/api/auth/login

GET Bearer Token https://palmcode.hidrogen.id/api/auth/me

GET Bearer Token https://palmcode.hidrogen.id/api/auth/logout
```

### Media

```
POST Bearer Token https://palmcode.hidrogen.id/api/api/media

GET Bearer Token https://palmcode.hidrogen.id/api/media

GET Bearer Token https://palmcode.hidrogen.id/api/media/{id}

DELETE Bearer Token https://palmcode.hidrogen.id/api/media/{id}
```

### Pages

```
POST Bearer Token https://palmcode.hidrogen.id/api/pages

GET Bearer Token https://palmcode.hidrogen.id/api/pages

GET Bearer Token https://palmcode.hidrogen.id/api/pages/{id}

PUT Bearer Token https://palmcode.hidrogen.id/api/pages/{id}

DELETE Bearer Token https://palmcode.hidrogen.id/api/pages/{id}
```

### Team Member

```
POST Bearer Token https://palmcode.hidrogen.id/api/team-members

GET Bearer Token https://palmcode.hidrogen.id/api/team-members

GET Bearer Token https://palmcode.hidrogen.id/api/team-members/{id}

POST Bearer Token https://palmcode.hidrogen.id/api/team-members/{id}

DELETE Bearer Token https://palmcode.hidrogen.id/api/team-members/{id}
```


## Unit Tests

Unit tests for the API endpoints, it's use the same database.

```
php artisan test

// or

php artisan test tests/Feature/API/PageControllerTest.php

php artisan test tests/Feature/API/TeamMemberControllerTest.php

php artisan test tests/Feature/API/MediaControllerTest.php
```

## Contributing

Please use the [issue tracker](https://github.com/teguharifudin/Headless-CMS/issues) to report any bugs or file feature requests.
