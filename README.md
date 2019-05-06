# lexer-parser

This module implements a simple (plus / minus / multiplication / division) mathematical Lexer & Parse service and make it available as a field formatter plugin in Drupal 8.

# Test

If you want to test this module please go to https://github.com/jacintocapote/amazee-lexer-parser and check steps.

# Structure

1. Over folder src/Shunt we have the parser/scanner classed based on the Shunting Yard Algorithm. More info https://en.wikipedia.org/wiki/Shunting-yard_algorithm
2. The service is implemented over src/LexerParserService file. This service has two method:
    - *ParserString*: Parse a string and return the eval value.
    - *ParserTree*: Get the initial tree.

# Use field Formatter

If you want to use the field formatter. Please go to add a new field to a content type. Select a Text Plain field and after create go to view mode and select the formatter Lexer & Parser. You can create content using the Lexer & Parser now.

**NOTE**: You need to use strings as *(10 + 20 -30 + 15 * 5)* for example. If you use a invalid string you will get a error message. If everything is ok and the parser works then with the hover over the string you will see a tree list. 
