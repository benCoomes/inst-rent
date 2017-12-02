# CU Instrument Rental Website
## Team Members
* Benjamin Coomes (bcoomes)
* Christopher West (cjwest)

## public_html

### css
Contains custom css files. Most styling is done via bootstrap delivered over a CDN.

### views
Contains a subdirectory for each 'view' defined by the application.  
  
 A view consists of a **template** html file which defines the layout of data and UI components for a page, and a javascript file which defines a **controller** for the view.  

 The controller fetches data from the backend, handles user interaction, and defines page behavior.  

 Check out the **instruments** view for a detailed comments. All other views are very similar in structure. 

### php
Contains the php code used to deliver data and execute queries on behalf of the front end. All functionality available on the front end is implemented in ajax_handlers.php.  

A class called ajax_handler is declared, which connects to the database using information provided in a specified configuration file.  
  

Once connected, the doAction method selects an action to perform, and performs the action if the user session has suffecient permissions and all necessary data has been provided. 

Upon completion of an action (successful or not), a response is returned. All responses are JSON encoded objects containing 'msg', 'status', and 'data' fields.  
  
Each action has a detailed comment section describing what data it expects, what constitutes success, and what error codes it may produce.


### app.js
Defines the controller for each view, a session loader service, and the behavior of the main page which loads views.

### index.html
Includes all dependencies, including the javascript files that define views. Sets the page header and the view pane where views are loaded.

## dbreset

### dbreset.php
file used to reset production database.

### dbreset_simple.php
file used to reset test database.

###test_data
contains files used to populate test and production databases, as well as populate.cpp, which we used to create the production datasets.