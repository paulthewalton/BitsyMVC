# VancouverTalksAbout Backend

SPA CMS with single post type (Topics) with drag-and-drop reordering on top of MVC structure.

## Setup

1. Clone repo
2. Install dependancies
    * JS dependancies `npm install`
    * PHP dependancies `composer install`
3. Build project files with `grunt`
4. Setup password protection
    * Configure `.htaccess` & `.htpasswd`
    * Configure endpoint.php with u/n and p/w
    * Move endpoint.php to outside password protected directory
5. Call Paul if problems because he didn't write this very thoroughly

All in all, it should look something like this:

```shell
cd path/to/project
git clone git@bitbucket.org:denmandigital/vancouvertalksabout-backend.git
cd vancouvertalksabout-backend
npm install
composer install
grunt
```

## VS Code
This project was built in VS Code and includes a workspace file for convenience. Opening that file (`bitsymvc.code-workspace`) will open VS Code in that folder and has some recommended extensions and settings for working on this project.
