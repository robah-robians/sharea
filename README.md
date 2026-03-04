# SHARE HOPE: Project Overview & Developer Guide

## 🎯 What is Share Hope?
**Share Hope** is a comprehensive, secure, and modern web platform designed to bridge the gap between Non-Governmental Organizations (NGOs) and private donors in Kenya. 

The primary aim of this platform is to foster **trust, transparency, and ease of use** in the charitable sector. It provides NGOs with a centralized digital space to showcase verified campaigns, while offering donors a secure, intuitive, and transparent environment to contribute financially to causes they care about.

## 💡 Why was it Built?
In many regions, donors hesitate to give because they cannot easily verify the legitimacy of a charity or see exactly where their money is going. **Share Hope** solves this by:
1. **Verifying NGOs:** A strict Admin approval process ensures only legitimate organizations can create funding campaigns.
2. **Interactive Transparency:** Utilizing interactive maps, detailed campaign pages, and progress bars to show real-time impact.
3. **Secure Transactions:** Providing bank-grade security features and robust local payment integrations (like M-Pesa).

---

## 🚀 Implemented Features Architecture

### 1. User Roles & Access Control (RBAC)
*   **Donors:** Can browse campaigns, securely donate, download PDF receipts, and track their giving history on a personal dashboard.
*   **NGOs:** Must be verified. Once approved, they can create campaigns, set funding goals, track progress, and export their donor data securely via CSV.
*   **Administrators:** Have a master dashboard to approve/reject NGO applications, view system-wide transaction volumes, export all data, and trigger Maintenance Mode.

### 2. Core Operational Features
*   **Campaign Management:** NGOs can upload images, set descriptions, and define financial goals for specific causes.
*   **Interactive Maps:** A global Leaflet.js/OpenStreetMap integration plots the exact geographical coordinates of all verified NGOs on the platform for complete transparency.
*   **Zero-Downtime Maintenance:** A secure toggle allowing Admins to safely take the platform offline for updates without corrupting active user data or crashing forms.

### 3. Payment & Financial Integrations
*   **M-Pesa (Sandbox):** Specifically localized for Kenya, allowing users to initiate push donations directly from their phones.
*   **Automated Receipts:** Generates instant, downloadable PDF receipts for tax records via `html2pdf.js`.
*   **Dynamic Goal Tracking:** Progress bars automatically calculate and update based on incoming donations, culminating in a visual **Confetti Celebration** when a goal hits 100%.

### 4. Advanced Security Measures 
*(Fully implemented to protect both the developer and the users)*
*   **Authentication:** BCrypt password hashing for all user accounts.
*   **Anti-XSS:** Strict output escaping (`htmlspecialchars`) on all dynamic content.
*   **Anti-CSRF:** Cryptographic, session-based tokens on every single form that alters the database (Logins, Registration, Donations).
*   **Anti-SQLi:** 100% strict usage of PDO Prepared Statements. Direct database queries are never used.
*   **Secure Password Resets:** Time-expiring, cryptographically secure email reset links.

### 5. UI / UX & Aesthetics
*   **Responsive Design:** Fully mobile-accessible with a dynamic slide-out hamburger menu and CSS Flexbox/Grid layouts.
*   **Premium Typography:** Utilizing the Google 'Montserrat' font for a strong, modern, and trustworthy aesthetic.
*   **Social Architecture:** Native, zero-cost 1-click sharing buttons for WhatsApp and Twitter integrated into every campaign.

---

## 🛠️ Technology Stack
This platform is a bespoke, lightweight, and incredibly fast application built *without* heavy external frameworks.
*   **Frontend:** HTML5, pure CSS3 (custom dynamic variables), Vanilla Javascript.
*   **Backend:** PHP (PDO).
*   **Database:** MySQL (Relational).

> *Note to Developer: This overview serves as your "True North." As you continue to scale Share Hope, refer back to the core aims of Trust, Transparency, and Security outlined in this document.*
