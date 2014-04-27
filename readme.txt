Generico Filter
===============
This is a filter that will allow up to ten templates to be registered.
When Moodle encounters a filter string it will use the data in the string to fill out the template, and insert it into the page.

eg
Template1
==============
templatekey = greenthings
template = There are many things that are green including these two i) @@greenthing1@@ and ii) @@greenthing2@@
templatedefaults= greenthing2=lettuce

If Moodle finds this filter string
{GENERICO:type=greenthings,greenthing1=peas}

It will replace it with:
There are many things that are green including these two i) peas and ii) lettuce

This example is pretty trivial of course, imagine using it to embed YouTube videos
{GENERICO:type=youtube,id=ABC12345678}

Or Quizlet flashcard sets
{GENERICO:type=quizlet,id=12314124,width=800,height=600}



