# ParentZoneDownloader
A hacky PHP script to download posts, images, videos and framework grading from Parent Zone

## Pre-Requisites
* Tested on PHP 7.4, but likely compatible with older versions
* Requires the curl extension to be installed and enabled.

## Configuration
Update the variables at the top of the file to configure your email address and password. 

## Running the script
Run `php downloader.php`

The script will then login, create a session on the Parent Zone website and starts downloading post data about all your children. It does all this using the underlying json API that the Parent Zone website itself uses. 

Within the configured download directory, the script will generate a directory for each child. e.g. `downloads/John Smith/`

Within each child's directory, each post will be saved to a separate directory. e.g. `downloads/John Smith/5388358/`

The post directory will contain `postdata.json`, any media files related to the post and a `gradings.json` if there was an assessment made by nursery staff as part the post.

Additionally, the script will create a `downloads/frameworks/` directory which will contain the detail about each of the frameworks that your child(ren) have been graded against. This is useful as the grading data itself references the framework and lacks context by itself.
