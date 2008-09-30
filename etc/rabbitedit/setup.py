from distutils.core import setup

data = (
    'templates/actions',
    'templates/elements',
    'templates/libs',
    'templates/units'
)

setup(
    name = 'rabbitedit',
    version = '1.0',
    url = 'http://www.kamibu.com/',
    author = 'Aristotelis Mikropoulos',
    author_email = 'amikrop@gmail.com',
    description = 'Edit source code files of Rabbit projects.',
    scripts = [ 'rabbitedit' ],
    data_files = (
        ( '/etc/rabbitedit/templates', data ),
        ( '/etc/rabbitedit', 'rabbitedit.conf' )
    )
)
