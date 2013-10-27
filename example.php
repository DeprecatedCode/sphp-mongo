title: "Using the MongoDB Plugin", plugin: "mongo"

# Import test runner

loc: "../../test/", @import(loc "test.php")

# Get a database connection

db: @plugin.mongo {host: "localhost"} "library"

books: db "books"

# Let's drop the collection before every test

['<pre>', books.drop.to_json, '</pre>'].print

_flush_()

result: books.insert [

  {title: "Do Androids Dream of Electric Sheep?", year: 1996, author: "Philip K. Dick"}

  {title: "Pride and Prejudice and Zombies",      year: 2009, author: "Seth Grahame-Smith"}

  {title: "The Hitchhiker's Guide to the Galaxy", year: 1995, author: "Douglas Adams"}

  {title: "I Was Told There'd Be Cake",           year: 2008, author: "Sloane Crosley"}

]

[ '<p>Have the books been inserted?</p><ul>'
  '<li>' (result{?it.ok = 1: "Yes on connection #" (it.connectionId), *: "No"}) '</li>'
  '</ul>' ].print
  
q: 0, result{++q (it.ok)}

'<p>Number of books that were successfully inserted: ' q '</p>'.print

_flush_()

# Let's see how many books there are

'<h2>There are ' (books.length {}) ' ' (books.name) ' in the collection.</h2>' .print

years: 1990..2013

['<table>',
  '<tr>'
    '<th>Year:</th>'
    years{'<th>' ("" it.replace ~"19|20" "'") '</th>'}
  '</tr>'
  '<tr>'
    '<th>Books:</th>'
    years{'<td>' (books.length {year: {$lte: it}}) '</td>'}
  '</tr>'
'</table>'].print

_flush_()

# Regex Query

"<p>Here's a list of authors of books that have the text 'and' in the title:</p>".print

results: books.find {title: ~'[Aa]nd'}

['<ul>', results{'<li>' (it.author) '</li>'}.join '', '</ul>'].print
