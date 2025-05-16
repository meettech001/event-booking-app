# Event Booking App

Welcome to the **Event Booking App**. Follow the steps below to set up the project on your local machine.

---

## 🚀 Setup Instructions

1. **Clone the Git Repository**
    ```bash
    git clone https://github.com/meettech001/event-booking-app.git
    ```

2. **Create `.env` File**
    - Copy the content from `.env.example` and paste it into a new `.env` file.

3. **Set Up Local Domain (Ubuntu)**
    - Edit your hosts file:
      ```bash
      sudo nano /etc/hosts
      ```
    - Append this line at the end:
      ```
      127.0.0.1 13.event-booking.mit
      ```
    - Save and exit.

4. **Give Permissions to the Storage Folder**
    ```bash
    sudo chmod 777 -R storage
    ```

5. **Ensure Docker & Docker Compose Are Installed**
    - Install [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/) if not already installed.

6. **Build and Start Docker Containers**
    - Navigate to the project directory:
      ```bash
      sudo docker compose build && sudo docker compose up -d
      ```

7. **Access Web Container**
    - List running containers:
      ```bash
      sudo docker ps
      ```
    - Enter the web container:
      ```bash
      sudo docker exec -it <container-name> bash
      ```

8. **Run Migrations and Seeders (Inside Container)**
    ```bash
    php artisan migrate:fresh --seed
    ```

9. **Run Tests (Inside Container)**
    ```bash
    php artisan test
    ```

---

## 📚 API Documentation

- **Swagger UI**:  
  [http://13.event-booking.mit/api/documentation](http://13.event-booking.mit/api/documentation)
  -To access url do following steps:
  ```bash
  sudo docker exec -it <web-container-name> bash
  and run below command:
  php artisan l5-swagger:generate
  ```
  ![Screenshot from 2025-05-16 20-52-33](https://github.com/user-attachments/assets/678dafa3-8f0b-4adf-b7b4-be275ae6c1ff)

## 📬 Postman ![Uploading Screenshot from 2025-05-16 20-52-33.png…]()
Collection

- **File Path**:  
    docs/Event-booking.postman_collection.json


---

## 🛠 Tech Stack

- Laravel
- PHP 8+
- Docker
- MySQL
- Sanctum Authentication
- Swagger (OpenAPI) Documentation

---

## 📣 Notes

- Always run Artisan commands like migrations, seeding, and testing **inside the Web container**.
- Ensure the `.env` file database credentials match your Docker settings.

---

## ✅ Quick Commands

| Purpose          | Command |
| ---------------- | ------- |
| Migrate & Seed   | `php artisan migrate:fresh --seed` |
| Run Tests        | `php artisan test` |
| Open Web Container | `sudo docker exec -it <container-name> bash` |
| Stop Containers  | `sudo docker compose down` |

---
