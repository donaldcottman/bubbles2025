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
