How Fenom works
===============

```

use Fenom;
use Fenom\Render;
use Fenom\Template;
use Fenom\Tokenizer;

______________________________
|                            |
| Fenom::display($tpl, $var) |
|____________________________|
              |
              | search the template
______________|___________________________
| Template loaded into Fenom::$_storage? |
|     Fenom::getTemplate($tpl)           |
|________________________________________|
              |                       |
              | yes                   | no
______________|__________             |
|  Render the template  |             |
| Render::display($tpl) |             |
|_______________________|             |
  |                                   |
  | (hot start)                       |
  |     ______________________________|__________________
  |     | Template already compiled and stored in cache |
  |     | Fenom::getTemplate($template)                 |
  |     |_______________________________________________|
  |                 |                              |
  |                 | yes                          | no
  |     ____________|_______________               |
  |     | Load template from cache |   not found   |
  |     | Fenom::_load(...)        |-------------->|
  |     |__________________________|               |
  |                 |                              |
  |                 | found                        |
  |     ____________|___________                   |
  |     | Validate template    |     invalid       |
  |     | Render::isValid(...) |------------------>|
  |     |______________________|                   |
  |                 |                              |
  |                 | valid                        |
  |     ____________|____________                  |
  |     |  Render the template  |                  |
  |<----| Render::display(...)  |                  |
  |     |_______________________|                  |
  |                                                |
  |     _____________________________      ________|___________________
  |     | Initialize compiler       |      | Compile the template     |
  |     | Template::load($tpl)      |<-----| Fenom::compile($tpl)     |
  |     |___________________________|      |__________________________|
  |                 |
  |     ____________|________________
  |     | Load template source      |
  |     | Provider::getSource($tpl) |
  |     |___________________________|
  |                 |
  |     ____________|______________
  |     | Start compilation       |
  |     | Template::compile($tpl) |
  |     |_________________________|
  |                 |
  |     ____________|______________
  |     | Search template tag     |
  |     | Template::compile($tpl) |<------------------------------------------------------|
  |     |_________________________|                                                       |
  |       |                 |                                                             |
  |       | not found       | found                                                       |
  |       |    _____________|_______________    _______________________________           |
  |       |    | Tokenize the tag's code   |    | Parse the tag               |           |
  |       |    | new Tokenizer($tag)       |--->| Template::parseTag($tokens) |           |
  |       |    |___________________________|    |_____________________________|           |
  |       |                                        |                  |                   |
  |       |                                 is tag |                  | is expression     |
  |       |      _______________________________   |   _______________|________________   |
  |       |      | Detect tag name             |   |   | Detect expression            |   |
  |       |      | Template::parseAct($tokens) |<---   | Template::parseAct($tokens)  |   |
  |       |      | Get callback by tag name    |       | Parse expression             |   |
  |       |      | Fenom::getTag($tag_name)    |       | Template::parseExpr($tokens) |   |
  |       |      |_____________________________|       |______________________________|   |
  |       |                   |                                       |                   |
  |       |                   | found                                 |                   |
  |       |    _______________|_______________                        |                   |
  |       |    | Invoke callback             |                        |                   |
  |       |    | Template::parseAct($tokens) |                        |                   |
  |       |    |_____________________________|                        |                   |
  |       |                   |                                       |                   |
  |       |    _______________|________________                       |                   |
  |       |    | Append code to template      |                       |                   |
  |       |    | Template::_appendCode($code) |<-----------------------                   |
  |       |    |______________________________|                                           |
  |       |                   |                                                           |
  |       |    _______________|___________                                                |
  |       |    | Finalize the tag        |                 starts search next tag         |
  |       |    | Template::compile($tpl) |>------------------------------------------------
  |       |    |_________________________|
  |       |
  |     __|___________________________________
  |     | Store template to cache            |
  |     | Fenom::compile($tpl)               |
  |     | Store template to Fenom::$_storage |
  |     | Fenom::getTemplate($tpl)           |
  |     |____________________________________|
  |                 |
  |     ____________|_____________
  |     | Render the template    |
  |     | Template::display(...) |
  |     |________________________|
  |         |
  |         | (cold start)
__|_________|________
|                   |
|       DONE        |
|___________________|

```