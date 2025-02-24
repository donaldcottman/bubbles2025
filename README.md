### Original developer instruction
Run the code in Visual Studio Code, then type "flask run" in the command line

### COPILOT OVERVIEW
### Purpose
The purpose of this project is to create a web application that interacts with the Robinhood trading platform. It allows users to perform various stock trading operations such as buying and selling stocks, as well as retrieving stock prices and user trading history. The application also includes functionality for managing user credentials and sessions securely.

### Key Features
1. **User Authentication and Registration**:
   - Users can register with their Robinhood credentials, which are encrypted and stored in a MySQL database.
   - The application supports logging in to Robinhood using stored credentials and generating TOTP (Time-based One-Time Password) for multi-factor authentication.

2. **Stock Trading Operations**:
   - Users can buy and sell stocks through the application.
   - The application supports different types of sell orders, including market orders, trailing stop orders, and stop-loss orders.

3. **Stock Price Retrieval**:
   - The application can fetch the latest stock prices using the Polygon.io API.

4. **Session Management**:
   - The application can replay stock trading sessions by reading and writing JSON files that store session data.

5. **API Endpoints**:
   - The application provides several API endpoints for interacting with the stock trading features:
     - `/bubbles_script`: Runs a script and returns session data.
     - `/replaybubblessession`: Replays a stock trading session.
     - `/bubbles_panelsliveupdate`: Fetches live stock price updates.
     - `/robinhoodregister`: Registers a user with Robinhood credentials.
     - `/buystock`: Buys a specified quantity of a stock.
     - `/sellstock`: Sells a specified quantity of a stock.
     - `/showrobinhoodhistory`: Shows the user's Robinhood trading history.

### Program Framework
1. **Flask**: The web framework used to create the API endpoints and handle HTTP requests.
2. **Flask-CORS**: Used to enable Cross-Origin Resource Sharing (CORS) for the Flask application.
3. **MySQL**: The database used to store user credentials and other data.
4. **Cryptography**: Used to encrypt and decrypt user credentials.
5. **Robin-Stocks**: A Python library for interacting with the Robinhood API.
6. **Polygon.io**: An API service used to fetch stock price data.
7. **Pushbullet**: A service used for sending push notifications (though not fully implemented in the provided code).

### Directory Structure
- **htdocs**: Main directory holding the web application code.
- **stack**: Contains various components and libraries used by the application, including PHP, MySQL, and Apache configurations.
- **bubblesAuto/data**: Directory for storing session data in JSON format.

### Summary
This project is a web application built using Flask that allows users to interact with the Robinhood trading platform. It provides features for user registration, stock trading, and session management. The application uses various APIs and libraries to fetch stock prices, manage user credentials securely, and perform trading operations.


### Grok3 Summary
Based on the screenshots and your context, I’ve reviewed the application running at https://github.com/donaldcottman/bubbles2025 (as of Feb 18, 2025). Here’s a detailed summary of your application, which will serve as a strong starting point for AI agents (or me) to collaborate with you on development, refactoring, and future enhancements. This summary captures the purpose, functionality, structure, and key components of the program.
Summary of the "Bubbles2025" Application

Overview
The "Bubbles2025" application is a web-based, real-time stock price visualization and trading simulation tool built with Python (likely Flask, given the Bitnami Lightsail setup and your earlier mentions) and JavaScript (for dynamic charting). It provides an interactive dashboard for users to monitor, analyze, and simulate trading for stock prices (e.g., BTF, QS) using data from sources like Robinhood. The app features a dark-themed UI, real-time charting, session management, and a trading interface, aimed at users (e.g., Donald Cottman) for educational or analytical purposes.

Key Features
Real-Time Stock Visualization:
Displays stock price changes (e.g., BTF, QS) as percentages over time, plotted on interactive charts.
Charts include multiple data points (e.g., volume in percent, delta force) with real-time updates (e.g., 4 ticks per second).
Supports multiple stocks (e.g., AEMD, NIKE, QUBT) with different colors for each (e.g., green, red, purple).
Includes zoom/pan controls (Min %, Max %, Auto Set X/Y) for customizing chart views.

Session Management:
Users can start and stop "test sessions" (e.g., "Test4_1 Session Archive") to record and replay stock data.
Sessions are logged with timestamps, durations, and user details (e.g., "Automatic" username).
Archive feature allows replaying past sessions, showing historical data like "Beginning of Day" sessions from December 2024.

Trading Simulation:
Interactive trading interface for buying/selling stocks (e.g., "Buy BTF," "Sell QS") with real-time price updates.
Displays current metrics like "Since Clicked," "Since Saved," price, volume, and delta (e.g., BTF: $1.63, Delta: 30.7%).
Includes trading controls (e.g., share quantity, trailing stop percentage) for simulated trades.

User Interface:
Dark-themed, responsive layout with purple and cyan accents.
Buttons for core actions: START, STOP, Robinhood Login, Auto Set X/Y, Buy/Sell.
Session archive table on the right, showing timestamps and session details.

User authentication (e.g., "Logged in as Donald Cottman," Logout button).

Data Sources:
Integrates with Robinhood for stock data (via the "Robinhood Login" button).
Likely uses a database or API to store/retrieve stock prices and session data (e.g., SQLite or a cloud service, given data/ in your repo).

Technical Stack (Inferred)
Backend: Python (Flask) running on Bitnami Lightsail, handling API calls, session management, and data processing.
Frontend: HTML/CSS/JavaScript, likely using a charting library (e.g., Chart.js, Plotly) for real-time graphs.
Database: Possibly SQLite (*.db in .gitignore) or a cloud database for stock and session data.
Deployment: Hosted on AWS Lightsail with Bitnami, accessible via ~/htdocs.

Current Repository Structure
Based on https://github.com/donaldcottman/bubbles2025:
bubblesAuto/: Likely contains core application logic (e.g., app.py or similar).
bubblesproject/: Possibly additional modules, scripts, or UI components.
.gitignore: Excludes venv/, __pycache__/, data/, login/, session/, etc., preventing sensitive or temporary files from being tracked.
Untracked folders like data/, login/, session/ suggest storage for runtime data or user sessions.

Key Challenges/Opportunities
Long File: You mentioned a single, lengthy file (e.g., app.py) needing refactoring into modular components (e.g., models, utils, routes).

Scalability: Real-time updates and trading simulations could benefit from better structure and caching.

Maintainability: Splitting logic into clear modules (e.g., data fetching, charting, trading) will make future updates easier.

Documentation: Adding comments or a README will help AI agents and collaborators understand the codebase.

Potential Next Steps for AI Agents/Collaboration
Identify the Long File: Confirm the exact file (e.g., bubblesAuto/app.py) and share its contents or structure for analysis.

Refactor Plan:

Create directories: models/, utils/, routes/, templates/, static/.

Move database logic to models/stock_model.py.

Move data fetching to utils/data_fetcher.py.

Move chart rendering to utils/chart_utils.py.

Move trading logic to routes/trading.py.

Update app.py to use these modules.

Enhancements:
Add error handling for Robinhood API calls.
Improve session archiving with pagination or search.
Optimize real-time updates for performance.
Testing: Write unit tests for key functions (e.g., data fetching, trading simulation).
Documentation: Create a README.md summarizing features, setup, and structure.

Starting Point for AI Agents
Repo URL: https://github.com/donaldcottman/bubbles2025.
Goal: Refactor the long file into a modular structure, improve scalability, and maintain real-time functionality.
Key Files: Identify app.py or equivalent in bubblesAuto/ or bubblesproject/.
