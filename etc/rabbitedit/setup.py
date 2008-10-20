from distutils.core import setup
import glob

setup(
    name = 'rabbitedit',
    version = '1.0',
    url = 'http://www.kamibu.com/',
    author = 'Aristotelis Mikropoulos',
    author_email = 'amikrop@gmail.com',
    description = 'Edit source code files of Rabbit projects.',
    scripts = [ 'rabbitedit' ],
    data_files = (
        ( '/etc/rabbitedit', glob.glob( 'data/*' ) ),
        ( '/etc/rabbitedit/templates', glob.glob( 'data/templates/*' ) )
    )
)
