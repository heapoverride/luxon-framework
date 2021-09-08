# Luxon's directories
This list only mentions directories that you're supposed to touch.

#### ./assets/
Great place to store your static files like images, scripts and stylesheets.

#### ./config/
This is where all Luxon's configuration files are

#### ./controllers/
Good place to store your controllers

#### ./errorpages/
Default luxon error pages that you can customize if you need to.

#### ./models/
Database models are very welcomed here

#### ./modules-disabled/
Disabled modules are here. Need something from here? Simply move it to `modules` directory to enable it.

#### ./other/
This is pretty good place to store custom components made with Html module.\
Consider making a sub directory `components` here for these components.

#### ./routes/
Obvious location to add your routes. Note that all files are loaded in this directory\
so you can split your routes across multiple files if you have to.

#### ./utils/
Utility functions go here.\
\
Note that `utils.php` here is actually used by Luxon framework and should\
not be modified but you can create an another file for your utility functions.

#### ./views/
Pretty space for all your views if you decide to use it.

