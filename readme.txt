Generico Filter
===============
Generico is a filter that will allow up to ten templates to be registered.
When Moodle encounters a filter string it will use the data in the filter string to fill out the template, and insert it into the page.

Usage
===============
Define templates at 
Site Administration / plugins / filters / Generico

A template consists of a "key", a "template," and some "defaults." 
The key is just a one word name, that tells Generico which template to use. 
The template is just passage of text that you want to use, and the parts of the template that you want to mark as variables you surround in @@ marks.
The defaults are a comma delimited list of variablename=value pairs. Here is an example template.

templatekey: greenthings
template: There are many green things including @@greenthing1@@ and ii) @@greenthing2@@
template defaults: greenthing2=lettuce

A possible filter string for this "greenthings" template would like this:
{GENERICO:type=greenthings,greenthing1=peas}

Generico would replace the above filter string with:
"There are many green things including peas and lettuce"

The filter string must follow this format,
{GENERICO:type=templatekey,variable1=data1,variable2=data2}

Th greenthings example above is trivial of course. Imagine using it to embed YouTube videos by registering the standard iframe code YouTube gives you, as a template.
Then it would only be necessary to insert the id of the video in a generico filter string.
{GENERICO:type=youtube,id=ABC12345678}

Installation
==============
If you are uploading Generico, first expand the zip file and upload the generico folder into:
[PATH TO MOODLE]/filters.

Then visit your Moodle server's Site Administration -> Notifications page. Moodle will guide you through the installation.
On the final page of the installation you will be able to register templates. You can choose to skip that and do it later from the 
Generico settings page if you wish.

After installing you will need to enable the Generico filter. 
You can enable the Generico filter when you visit:
Site Administration / plugins / filters / manage filters

Enjoy

Justin Hunt
poodllsupport@gmail.com





