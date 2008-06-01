<?php
    /*
    //
    //   Simple stand-alone object
    //
    
    class Place extends Satori {
        protected $mDbTableAlias = 'places';
    }
    
    class Bulk extends Satori {
        protected $mDbTableAlias = 'bulk';
    }
    
    //
    //   One-to-one relationship
    //
    
    class Space extends Satori {
        protected $mDbTableAlias = 'spaces';
        protected $mDb = 'db';
        
        protected function Relations() {
            $this->mBulk = $this->HasOne( 'Bulk', 'bulkid' );
        }
    }
    
    //
    //   One-to-many relationship
    //
    
    class Photo extends Satori {
        protected $mDbTableAlias = 'photos';
        
        protected function Relations() {
            $this->mAlbum = $this->HasOne( 'Album', 'albumid' );
        }
    }
    
    class Album extends Satori {
        protected $mDbTableAlias = 'albums';
        
        protected function Relations() {
            $this->mPhotos = $this->HasMany( 'PhotoFinder', 'FindByAlbum', 'albumid' );
            $this->mUser = $this->HasOne( 'User', 'userid' );
        }
    }
    
    //
    //   Many-to-many relationship
    //
    
    class User extends Satori {
        protected $mDbTableAlias = 'users';
        
        protected function Relations() {
            $this->mJournalAuthors = $this->HasMany( 'JournalAuthorshipFinder', 'FindByUser', $this );
            $this->mSettings = $this->HasOne( 'UserSetting', 'user_id' );
            $this->mProfiles = $this->HasOne( 'UserProfile', 'user_id' );
            $this->mPlace = $this->HasOne( 'Place', 'placeid' );
            $this->mAlbums = $this->HasMany( 'AlbumFinder', 'FindByUser', $this );
        }
    }
    
    class JournalAuthorship extends Satori {
        protected $mDbTableAlias = 'journalauthors';
        
        protected function Relations() {
            $this->mJournal = $this->HasOne( 'Journal', 'journalid' );
            $this->mAuthor = $this->HasOne( 'User', 'userid' );
        }
    }
        
    class UserSetting extends Satori {
        protected $mDbTableAlias = 'usersettings';
    }
    
    class Journal extends Satori {
        protected $mDbTableAlias = 'journals';
        
        public function GetText() {
            return $this->Bulk->Text;
        }
        public function Relations() {
            $this->mJournalAuthors = $this->HasMany( 'JournalAuthor', 'journal_journalid', array( 'journals_authors', 'journalauthor_journalid' ) );
            $this->mBulk = $this->HasOne( 'Bulk', 'journal_bulkid', array( 'bulk', 'bulk_id' ) );
        }
    }
    
    $theuser = New User( 5 );
    foreach ( $theuser->Journals as $journal ) {
        ?><h1><?php
        echo htmlspecialchars( $journal->Title );
        ?></h1><?php
        echo $journal->Text;
    }
    
    echo htmlspecialchars( $theuser->Profiles->FavouriteMovies->GetByIndex( 2 ) );
    */
    class TestRabbitOverloadable extends Overloadable {
        private $mFoo;
        
        public function TestRabbitOverloadable() {
            $this->mFoo = false;
        }
        public function SetFoo( $value ) {
            $this->mFoo = $value;
        }
        public function GetBar() {
            return $this->mFoo;
        }
    }
    
    class TestRabbitSatoriExtension extends Satori {
        protected $mDbTableAlias = 'rabbit_satori_test';
        protected $mOnDeleteNumCalls = 0;
        protected $mOnCreateNumCalls = 0;
        protected $mOnUpdateNumCalls = 0;

        protected function GetOnDeleteNumCalls() {
            return $this->mOnDeleteNumCalls;
        }
        protected function GetOnCreateNumCalls() {
            return $this->mOnCreateNumCalls;
        }
        protected function GetOnUpdateNumCalls() {
            return $this->mOnUpdateNumCalls;
        }
        public function LoadDefaults() {
            $this->Char = 'abcd';
            $this->Name = 'coco';
        }
        protected function OnDelete() {
            ++$this->mOnDeleteNumCalls;
        }
        protected function OnCreate() {
            ++$this->mOnCreateNumCalls;
        }
        protected function OnUpdate() {
            ++$this->mOnUpdateNumCalls;
        }
        protected function GetDb() {
            return $this->mDb;
        }
    }
    
    class TestRabbitSatoriExtensionFinder extends Finder {
        protected $mModel = 'TestRabbitSatoriExtension';
        
        public function FindUnique( $id ) {
            w_assert( is_int( $id ) );
            $prototype = New TestRabbitSatoriExtension();
            $prototype->Id = $id;
            return $this->FindByPrototype( $prototype );
        }
        public function FindSQL() {
            $query = $this->mDb->Prepare(
                'SELECT
                    *
                FROM
                    :rabbit_satori_test;'
            );
            $query->BindTable( 'rabbit_satori_test' );
            $res = $query->Execute();
            $this->FindBySQLResult( $res );
        }
        public function FindAll() {
            return $this->FindByPrototype( New TestRabbitSatoriExtension() );
        }
        public function FindByCharInOrder( $char ) {
            $prototype = New TestRabbitSatoriExtension();
            $prototype->Char = $char;
            $offset = 0;
            $limit = 3;
            return $this->FindByPrototype( $prototype, $offset, $limit, array( 'test_id', 'DESC' ) );
        }
    }
        
    class TestRabbitSatori extends Testcase {
        protected $mAppliesTo = 'libs/rabbit/activerecord/satori';
        private $mDb;
        private $mDbTable;
        private $mObj;
        
        public function SetUp() {
            global $rabbit_settings;
            
            w_assert( is_array( $rabbit_settings[ 'databases' ] ) );
            w_assert( count( $rabbit_settings[ 'databases' ] ) );
            $databasealiases = array_keys( $rabbit_settings[ 'databases' ] );
            w_assert( isset( $GLOBALS[ $databasealiases[ 0 ] ] ) );
            $this->mDb = $GLOBALS[ $databasealiases[ 0 ] ];
            w_assert( $this->mDb instanceof Database );
            
            // make sure we don't overwrite something
            w_assert( $this->mDb->TableByAlias( 'rabbit_satori_test' ) === false );
            
            $this->mDbTable = New DBTable();
            $this->mDbTable->Name = 'rabbit_satori_test';
            $this->mDbTable->Alias = 'rabbit_satori_test';
            $this->mDbTable->Database = $this->mDb;
            
            $field = New DBField();
            $field->Name = 'test_id';
            $field->Type = DB_TYPE_INT;
            $field->IsAutoIncrement = true;
            
            $field2 = New DBField();
            $field2->Name = 'test_char';
            $field2->Type = DB_TYPE_CHAR;
            $field2->Length = 4;
            
            $field3 = New DBField();
            $field3->Name = 'test_int';
            $field3->Type = DB_TYPE_INT;
            
            $field4 = New DBField();
            $field4->Name = 'test_name';
            $field4->Type = DB_TYPE_CHAR;
            $field4->Length = 4;
            
            $this->mDbTable->CreateField( $field, $field2, $field3, $field4 );
            
            $primary = New DBIndex();
            $primary->Type = DB_KEY_PRIMARY;
            $primary->AddField( $field );
            
            $this->mDbTable->CreateIndex( $primary );
            
            $this->mDbTable->Save();
            
            $this->mDb->AttachTable( 'rabbit_satori_test', 'rabbit_satori_test' );
        }
        public function TestClassesExist() {
            $this->Assert( class_exists( 'Overloadable' ), 'Class Overloadable is undefined' );
            $this->Assert( class_exists( 'Satori' ), 'Class Satori is undefined' );
        }
        public function TestOverloadable() {
            $this->Assert( class_exists( 'TestRabbitOverloadable' ) );
            $test = New TestRabbitOverloadable();
            $this->Assert( $test instanceof TestRabbitOverloadable );
            $this->AssertEquals( false, $test->Bar, 'Initial value of Foo is not false as expected' );
            $test->Foo = 5;
            $this->AssertEquals( 5, $test->Bar, 'Value of Foo should have been changed to 5' );
            $test->Foo = false;
            $this->AssertEquals( false, $test->Bar, 'Value of Foo should have been changed back to false' );
            $test->Foo = true;
            $this->AssertEquals( true, $test->Bar, 'Value of Foo should have been changed to true' );
            $test->Foo = 'Somestring';
            $this->AssertEquals( 'Somestring', $test->Bar, 'Unable to change value of Foo to an arbitrary string' );
            $test->Foo = array( 2, 3, 5, 7, 11 );
            $this->AssertEquals( array( 2, 3, 5, 7, 11 ), $test->Bar, 'Unable to change value of Foo to a non-scalar value' );
            $test->Foo = $this;
            $this->AssertEquals( $this, $test->Bar, 'Unable to change value of Foo to an object' );
        }
        public function TestCreation() {
            global $rabbit_settings;
            
            $this->Assert( class_exists( 'TestRabbitSatoriExtension' ) );
            $this->mObj = New TestRabbitSatoriExtension();
            reset( $rabbit_settings[ 'databases' ] );
            $this->AssertEquals( $this->mObj->Db, $GLOBALS[ key( $rabbit_settings[ 'databases' ] ) ] );
            $this->AssertFalse( $this->mObj->Exists(), 'New Satori-derived object should not exist prior to saving' );
            $this->AssertEquals( false, $this->mObj->Id, 'Prior to saving, all domain attributes should be false (0)' );
            $this->AssertEquals( false, $this->mObj->Int, 'Prior to saving, all domain attributes should be false (1)' );
            $this->AssertEquals( false, $this->mObj->Char, 'Prior to saving, all domain attributes should be false (2)' );
            $this->AssertEquals( false, $this->mObj->Name, 'Prior to saving, all domain attributes should be false (3)' );
            $this->mObj->Name = 'haha';
            $this->AssertEquals( 'haha', $this->mObj->Name, 'Prior to saving, modified domain attributes should reflect modifications' );
            $this->AssertEquals( 0, $this->mObj->OnDeleteNumCalls, 'Prior to saving, no calls must be made to OnDelete by Satori' );
            $this->AssertEquals( 0, $this->mObj->OnUpdateNumCalls, 'Prior to saving, no calls must be made to OnUpdate by Satori' );
            $this->AssertEquals( 0, $this->mObj->OnCreateNumCalls, 'Prior to saving, no calls must be made to OnCreate by Satori' );
            $this->mObj->Save();
            $this->AssertTrue( $this->mObj->Exists(), 'New Satori-derived object should exist after saving' );
            $this->AssertEquals( 1, $this->mObj->Id, 'Auto-increment fields should be filled-in after entry creation' );
            $this->AssertEquals( 0, $this->mObj->OnDeleteNumCalls, 'After saving, no calls must be made to OnDelete by Satori' );
            $this->AssertEquals( 0, $this->mObj->OnUpdateNumCalls, 'After saving, no calls must be made to OnUpdate by Satori' );
            $this->AssertEquals( 1, $this->mObj->OnCreateNumCalls, 'After saving, one calls must be made to OnCreate by Satori' );
        }
        public function TestDefaults() {
            $this->AssertEquals( 'abcd', $this->mObj->Char, 'Default values did not load using LoadDefaults()' );
            $this->AssertEquals( 0, $this->mObj->Int, 'Default values not set using LoadDefaults() should default to the default value of the given type' );
            $this->AssertEquals( 'haha', $this->mObj->Name, 'Default values should not override explicitly specified values' );
        }
        public function TestLookup() {
            $obj = New TestRabbitSatoriExtension( $this->mObj->Id );
            $this->AssertEquals( $this->mObj->Id, $obj->Id, 'Retrieved object should match the one saved (Id)' );
            $this->AssertEquals( $this->mObj->Int, $obj->Int, 'Retrieved object should match the one saved (Int)' );
            $this->AssertEquals( $this->mObj->Char, $obj->Char, 'Retrieved object should match the one saved (Char)' );
        }
        public function TestFetchedArray() {
            $obj = New TestRabbitSatoriExtension(
                array(
                    'test_id' => 512,
                    'test_char' => 'q',
                    'test_int' => 974,
                    'test_name' => 'tree'
                )
            );
            $this->Assert( $obj->Exists(), 'Objects created using fetched arrays must exist' );
            $this->AssertEquals( 512, $obj->Id, 'Could not set Id through fetched array' );
            $this->AssertEquals( 'q', $obj->Char, 'Could not set Char through fetched array' );
            $this->AssertEquals( 974, $obj->Int, 'Could not set Int through fetched array' );
            $this->AssertEquals( 'tree', $obj->Name, 'Could not set Name through fetched array' );
        }
        public function TestAssignment() {
            $this->mObj->Char = 'cool';
            $this->AssertEquals( 'cool', $this->mObj->Char, 'Could not assign string Satori attribute' );
            $this->mObj->Int = 5;
            $this->AssertEquals( 5, $this->mObj->Int, 'Could not assign integer Satori attribute' );
            $this->AssertEquals( 1, $this->mObj->Id, 'First object auto-increment ID should be 1' );
            $this->Assert( is_int( $this->mObj->Id ), 'Autoincrement fields should be ints' );
            $this->Assert( is_int( $this->mObj->Int ), 'Integer fields should be ints' );
        }
        public function TestUpdate() {
            $this->mObj = New TestRabbitSatoriExtension( $this->mObj->Id ); // return to persistent state
            $this->mObj->Int = 42;
            $this->mObj->Char = 'neat';
            $caught = false;
            try {
                $this->mObj->Id = 1337;
            }
            catch ( SatoriException $e ) {
                $caught = true;
            }
            $this->Assert( $caught, 'Read-only satori attributes should not be changeable' );
            $this->AssertEquals( 1, $this->mObj->Id, 'Read-only satori attributes should remain unchanged after exception is thrown' );
            $this->AssertEquals( 0, $this->mObj->OnUpdateNumCalls, 'Prior to updating, no calls must be made to OnUpdate by Satori' );
            $this->mObj->Save();
            $this->AssertEquals( 1, $this->mObj->OnUpdateNumCalls, 'After updating, one call must be made to OnUpdate by Satori' );
            $this->Assert( $this->mObj->Exists(), 'Object should still exist after saving' );
            $this->AssertEquals( 1, $this->mObj->Id, 'Auto-increment value should remain unchanged after updating object' );
            $this->AssertEquals( 'neat', $this->mObj->Char, 'Issuing save to update should not affect char value' );
            $this->AssertEquals( 42, $this->mObj->Int, 'Issuing save to update should not affect int value' );
            $this->mObj = New TestRabbitSatoriExtension( $this->mObj->Id) ; // return to persistent state
            $this->AssertEquals( 1, $this->mObj->Id, 'Auto-increment value was incorrectly updated' );
            $this->AssertEquals( 'neat', $this->mObj->Char, 'Char value was incorrectly updated' );
            $this->AssertEquals( 42, $this->mObj->Int, 'Int value was incorrectly updated' );
        }
        public function TestNonExisting() {
            $obj = New TestRabbitSatoriExtension( $this->mObj->Id + 1 );
            $this->AssertFalse( $obj->Exists(), 'Non-existing objects should not exist' );
        }
        public function TestPrototyping() {
            $obj = New TestRabbitSatoriExtension();
            $this->AssertEquals( array(), $obj->FetchPrototypeChanges(), 'Non-modified object should return no prototype changes' );
            $obj->Char = '-ko-';
            $this->AssertEquals( array( 'test_char' => '-ko-' ), $obj->FetchPrototypeChanges(), 'Prototype changes should be reflected by FetchPrototypeChanges()' );
            $obj->Id = 1;
            $this->AssertEquals( array( 'test_id' => 1, 'test_char' => '-ko-' ), $obj->FetchPrototypeChanges(), 'Multiple prototype changes should be reflected by FetchPrototypeChanges()' );
        }
        public function TestFinder() {
            $finder = New TestRabbitSatoriExtensionFinder();
            $this->Assert( is_object( $finder ), 'Finders must be objects' );
            $this->Assert( $finder instanceof TestRabbitSatoriExtensionFinder, 'Finders must be objects of the desired class' );
            $all = $finder->FindAll();
            $this->Assert( is_array( $all ), 'Group finder functions must return arrays' );
            $this->AssertEquals( 1, count( $all ), 'Group finder function for "all" must return 1 when only 1 object exists' );
            $one = $finder->FindUnique( 1 );
            $this->Assert( is_object( $one ), 'Unique finder functions must return objects' );
            $none = $finder->FindUnique( 1337 );
            $this->AssertFalse( $none, 'Unique finder functions must return false if they can\'t find target' );
        }
        public function TestDeletion() {
            $this->AssertEquals( 0, $this->mObj->OnDeleteNumCalls, 'Prior to deleting, no calls must be made to OnDelete by Satori' );
            $this->mObj->Delete();
            $this->AssertFalse( $this->mObj->Exists(), 'Satori-derived object should not exist after deletion' );
            $this->AssertEquals( 1, $this->mObj->OnDeleteNumCalls, 'After deleting, one call must be made to OnDelete by Satori' );
        }
        public function TearDown() {
            $this->mDbTable->Delete();
            $this->mDb->DetachTable( 'rabbit_satori_test' );
        }
    }
    
    return New TestRabbitSatori();
?>
