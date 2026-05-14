# Project Name

> A desktop application built with **JavaFX** (frontend), **Symfony** (REST API backend), and a **Python Flask microservice** for face recognition.

---

## Architecture

```
┌─────────────────┐        HTTP/REST        ┌──────────────────┐
│   JavaFX Client │ ──────────────────────► │  Symfony API     │
│   (Desktop UI)  │ ◄────────────────────── │  (Backend)       │
└─────────────────┘                         └────────┬─────────┘
                                                     │ HTTP
                                                     ▼
                                            ┌──────────────────┐
                                            │  Flask Service   │
                                            │ (Face Recognition│
                                            │  Microservice)   │
                                            └──────────────────┘
```

| Component | Technology | Port |
|-----------|-----------|------|
| Desktop Client | JavaFX | — |
| REST API | Symfony 6.x | 8000 |
| Face Recognition | Python Flask | 5000 |

---

## Tech Stack

- **Frontend:** JavaFX 17+, Scene Builder
- **Backend:** Symfony 6.x, PHP 8.1+, Doctrine ORM
- **Microservice:** Python 3.10+, Flask, Pillow
- **Database:** MySQL / PostgreSQL
- **Build Tools:** Maven (JavaFX), Composer (Symfony), pip (Flask)

---

## Prerequisites

Make sure the following are installed on your machine:

| Tool | Version | Download |
|------|---------|---------|
| PHP | 8.1+ | https://www.php.net |
| Composer | latest | https://getcomposer.org |
| Symfony CLI | latest | https://symfony.com/download |
| Java JDK | 17+ | https://adoptium.net |
| Maven | 3.8+ | https://maven.apache.org |
| Python | 3.10+ | https://www.python.org |
| pip | latest | bundled with Python |
| MySQL/PostgreSQL | — | https://www.mysql.com |

---

## Project Structure

```
project-root/
├── symfony-api/          # Symfony backend
├── javafx-client/        # JavaFX desktop app
├── flask-service/        # Python face recognition microservice
│   └── app.py
└── README.md
```

---

## Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/your-project.git
cd your-project
```

---

### 2. Symfony Backend

```bash
cd symfony-api

# Install dependencies
composer install

# Copy and configure environment
cp .env .env.local
```

Edit `.env.local` and set your database credentials:

```env
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name"
FLASK_SERVICE_URL="http://127.0.0.1:5000"
```

```bash
# Create the database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# (Optional) Load fixtures
php bin/console doctrine:fixtures:load

# Start the Symfony server
symfony server:start --port=8000
```

The API will be available at: `http://127.0.0.1:8000`

---

### 3. Flask Microservice

```bash
cd flask-service

# Install dependencies
pip install flask pillow

# Start the microservice
python app.py
```

The microservice will be available at: `http://127.0.0.1:5000`

> **Production tip:** Use Gunicorn instead of the built-in Flask server:
> ```bash
> pip install gunicorn
> gunicorn -w 4 -b 0.0.0.0:5000 app:app
> ```

---

### 4. JavaFX Client

```bash
cd javafx-client

# Build the project
mvn clean install

# Run the application
mvn javafx:run
```

> Make sure the API base URL in the JavaFX config points to `http://127.0.0.1:8000`.

---

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DATABASE_URL` | Database connection string | — |
| `FLASK_SERVICE_URL` | URL of the Flask microservice | `http://127.0.0.1:5000` |
| `APP_ENV` | Symfony environment (`dev`/`prod`) | `dev` |
| `APP_SECRET` | Symfony secret key | — |

---

## API Endpoints

### Symfony REST API (`http://127.0.0.1:8000`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login` | User authentication |
| GET | `/api/users` | Get all users |
| POST | `/api/users` | Create a user |
| PUT | `/api/users/{id}` | Update a user |
| DELETE | `/api/users/{id}` | Delete a user |

### Flask Microservice (`http://127.0.0.1:5000`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/extract` | Extract face descriptor from image |

**Example request to `/extract`:**
```json
{
  "image": "<base64-encoded-image-or-data-url>"
}
```

**Example response:**
```json
{
  "descriptor": [0.12, -0.45, 0.78, "...128 values total"]
}
```

---

## Running with Docker (Optional)

```yaml
# docker-compose.yml
services:
  symfony-api:
    build: ./symfony-api
    ports:
      - "8000:8000"
    environment:
      DATABASE_URL: mysql://user:password@db:3306/mydb

  flask-service:
    build: ./flask-service
    ports:
      - "5000:5000"

  db:
    image: mysql:8
    environment:
      MYSQL_ROOT_PASSWORD: password
      MYSQL_DATABASE: mydb
```

```bash
docker-compose up --build
```

---

## Screenshots

> *(Add screenshots here)*

| Login Screen | Dashboard | Face Recognition |
|---|---|---|
| ![Login](screenshots/login.png) | ![Dashboard](screenshots/dashboard.png) | ![Face](screenshots/face.png) |

---

## Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add my feature'`
4. Push to the branch: `git push origin feature/my-feature`
5. Open a Pull Request

---

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## Authors
