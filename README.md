# Esportify – eSports Management Platform

## 📌 Overview
This project was developed as part of the *PIDEV – 3rd Year Engineering Program* at *Esprit School of Engineering* (Academic Year 2025–2026).

Esportify is a web application designed to manage eSports activities, including tournaments, teams, players, and matches. It provides an interactive platform for users to explore competitions, track performance, and engage with the eSports ecosystem.

---

## 🚀 Features
- 🔐 User authentication & role management (Admin / Player / User)
- 🏆 Tournament management (create, update, delete)
- 👥 Team and player management
- 🎮 Match scheduling and results tracking
- 📊 Performance statistics and rankings
- 🔎 Search and filter system
- 🌐 Responsive web interface

---

## 🛠️ Tech Stack

### Backend
- Symfony (PHP Framework)
- Doctrine ORM

### Frontend
- Twig
- HTML / CSS / JavaScript

### Database
- MySQL

---

## 🏗️ Architecture
The application follows the *MVC (Model-View-Controller)* architecture:

- *Model*: Doctrine entities (User, Team, Tournament, Match, etc.)
- *View*: Twig templates
- *Controller*: Symfony controllers handling business logic

---

## 👨‍💻 Contributors
- Rajhi Mohamed Aziz
- Chaabani Sarra
- Saada Maryem
- Benmansour Salma
- Jery Mouhamed Amine
- Laamouri Tayssir

---

## 🎓 Academic Context
Developed at *Esprit School of Engineering – Tunisia*  
PIDEV – 3A | 2025–2026  

---

## ⚙️ Getting Started

### Prerequisites
- PHP >= 8.x
- Composer
- Symfony CLI
- MySQL

### Installation

```bash
# Clone the repository
git clone https://github.com/your-username/esportify.git

# Navigate to project directory
cd esport

# Install dependencies
composer install

# Configure environment variables
cp .env .env.local

# Update DATABASE_URL in .env.local

# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Start Symfony server
symfony server:start
