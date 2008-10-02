from distutils.core import setup

templates = (
    'data/templates/actions',
    'data/templates/elements',
    'data/templates/libs',
    'data/templates/units'
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
        ( '/etc/rabbitedit/templates', templates ),
        ( '/etc/rabbitedit', [ 'data/rabbitedit.conf', 'data/rules' ] )
    )
)
