<?php
    abstract class tBaseType {
        protected $mValue;
        protected $mExists;
        
        public function __construct( $value ) {
            $this->mExists = $value !== false;
        }
        public function Exists() {
            return $this->mExists;
        }
        public function Get() {
            return $this->mValue;
        }
        public function __toString() {
            return ( string )$this->Get();
        }
    }
    
    class tInteger extends tBaseType {
        public function __construct( $value ) {
            $this->mValue = ( integer )$value;
            parent::__construct( $value );
        }
        public function Get( $domain = false ) {
            if ( $domain !== false ) {
                w_assert( is_array( $domain ) );
                if ( isset( $domain[ 'min' ] ) ) {
                    w_assert( is_int( $domain[ 'min' ] ) );
                    if ( $this->mValue < $domain[ 'min' ] ) {
                        $this->mValue = $domain[ 'min' ];
                    }
                }
                if ( isset( $domain[ 'max' ] ) ) {
                    w_assert( is_int( $domain[ 'max' ] ) );
                    if ( $this->mValue > $domain[ 'max' ] ) {
                        $this->mValue = $domain[ 'max' ];
                    }
                }
            }
            return parent::Get();
        }
    }
    
    class tFloat extends tBaseType {
        public function __construct( $value ) {
            $this->mValue = ( float )$value;
            parent::__construct( $value );
        }
    }
    
    class tBoolean extends tBaseType {
        public function __construct( $value ) {
            if ( $value === 'yes' || $value === 'true' || $value === '1' || $value === 1 ) {
                $this->mValue = true;
            }
            else if ( $value === 'no' || $value === 'false' || $value === '0' || $value === 0 ) {
                $this->mValue = false;
            }
            else {
                $this->mValue = ( bool )$value;
            }
            parent::__construct( $value );
        }
    }
    
    class tString extends tBaseType {
        public function __construct( $value ) {
            $this->mValue = ( string )$value;
            parent::__construct( $value );
        }
        public function Get( $domain = false ) {
            if ( $domain !== false ) {
                w_assert( is_array( $domain ) );
                if ( isset( $domain[ 'maxlength' ] ) ) {
                    w_assert( is_int( $domain[ 'maxlength' ] ) );
                    if ( mb_strlen( $this->mValue ) > $domain[ 'maxlength' ] ) {
                        // crop it
                        $this->mValue = mb_substr( $this->mValue, 0, $domain[ 'maxlength' ] );
                    }
                }
            }
            return parent::Get();
        }
    }
    
    class tText extends tString {
        public function __construct( $value ) {
            parent::__construct( $value );
            $this->mValue = iconv( 'UTF-8', 'UTF-8', $this->mValue ); // ensure UTF-8 is well-formed; if not, filter out illegal characters
        }
        
    }

    abstract class tArray extends tBaseType implements Iterator {
        protected $mValues;
        
        public function __construct( $values, $basetype ) {
            w_assert( is_string( $basetype ), '$basetype, second parameter to tArray constructor from your custom type, must be a string' );
            w_assert( class_exists( $basetype ), '$basetype, second parameter to tArray constructor from your custom type, cannot be the empty string' );
            
            $baseclass = New ReflectionClass( $basetype );
            
            w_assert( $baseclass->isSubclassOf( New ReflectionClass( 'tBaseType' ) ), '$basetype, second parameter to tArray constructor from your custom type-safe type, is expected to be a string of a class name derived from tBaseType' );
            
            $this->mValues = array();
            if ( empty( $values ) ) { // false
                return;
            }
            if ( !is_array( $values ) ) {
                // single array value
                $values = array( $values );
            }
            foreach ( $values as $value ) {
                $this->mValues[] = New $basetype( $value ); // MAGIC!
            }
        }
        public function rewind() {
            return reset($this->mValues);
        }
        public function current() {
            return current($this->mValues);
        }
        public function key() {
            return key($this->mValues);
        }
        public function next() {
            return next($this->mValues);
        }
        public function valid() {
            return $this->current() !== false;
        }
        public function Get() {
            throw New Exception( 'Type Get() cannot be used on tArray; iterate over tArray and ->Get() on each value instead' );
        }
    }
    
    class tIntegerArray extends tArray {
        public function __construct( $values ) {
            parent::__construct( $values, 'tInteger' );
        }
    }

    class tFloatArray extends tArray {
        public function __construct( $values ) {
            parent::__construct( $values, 'tFloat' );
        }
    }
    
    class tBooleanArray extends tArray {
        public function __construct( $values ) {
            parent::__construct( $values, 'tBoolean' );
        }
    }

    class tStringArray extends tArray {
        public function __construct( $values ) {
            parent::__construct( $values, 'tString' );
        }
    }

    class tTextArray extends tArray {
        public function __construct( $values ) {
           parent::__construct( $values, 'tText' );
        }
    }

    class tCoalaPointer extends tString {
        public function __construct( $value ) {
            parent::__construct( $value );
            $this->mExists = $value != '0';
            w_assert( preg_match( '#^([a-zA-Z0-9\.\[\] ])+$#', $this->mValue ) );
        }
        public function Get() {
            throw New Exception( 'Type Get() cannot be used on tCoalaPointer; use "echo" directly with your pointer instead' );
        }
        public function __toString() {
            return $this->mValue;
        }
    }
    
    class tFile extends tBaseType {
        private $mName;
        private $mMimetype;
        private $mSize;
        private $mTempname;
        private $mErrorcode;

        public function __get( $name ) {
            switch ( $name ) {
                case 'Name':
                    return $this->mName;
                case 'Mimetype':
                    return $this->mMimetype;
                case 'Size':
                    return $this->mSize;
                case 'Tempname':
                    return $this->mTempname;
                case 'ErrorCode':
                    return $this->mErrorCode;
            }
            // else return nothing
        }
        public function __construct( $value ) {
            $this->mExists = false;
            if ( !is_array( $value ) ) {
                return;
            }
            if ( !isset( $value[ 'tmp_name' ] ) ) {
                return;
            }
            if ( !is_uploaded_file( $value[ 'tmp_name' ] ) ) {
                return;
            }
            if ( !isset( $value[ 'name' ] ) ) {
                $value[ 'name' ] = '';
            }
            if ( !isset( $value[ 'type' ] ) ) {
                $value[ 'type' ] = '';
            }
            if ( !isset( $value[ 'size' ] ) ) {
                $value[ 'size' ] = 0;
            }

            $this->mExists    = true;
            $this->mName      = $value[ 'name' ];
            $this->mMimetype  = $value[ 'type' ]; // mime type, if the browser provided such information
            $this->mSize      = $value[ 'size' ]; // in bytes
            $this->mTempname  = $value[ 'tmp_name' ];
            $this->mErrorcode = $value[ 'error' ];
        }
        public function __toString() {
            return '[uploaded file: ' . $this->mName . ']';
        }
        public function Get() {
            throw New Exception( 'Type Get() cannot be used on tFile; use build-in methods and attributes directly with your file object instead' );
        }
    }

    function Rabbit_TypeSafe_Call( $function , $req ) {
        w_assert( is_array( $req ) );

        $basetype = New ReflectionClass( 'tBaseType' );
        
        // reflect!
        if ( is_array( $function ) ) {
            // if $function is a class name and a method name...
            w_assert( is_array( $function ) );

            $obj = $function[ 0 ];
            $class = get_class( $function[ 0 ] );
            $method = $function[ 1 ];

            $refl = New ReflectionClass( $class );
            $func = $refl->getMethod( $method );
        }
        else {
            // if $function is a simple function...
            $func = New ReflectionFunction( $function );
        }

        $params = array();
        
        foreach ( $func->getParameters() as $i => $parameter ) {
            $paramname = $parameter->getName();
            $paramclass = $parameter->getClass();
            if ( !is_object( $paramclass ) ) {
                throw New Exception( 'No type hinting specified for parameter ' . $paramname . ' of type-safe function ' . $function );
            }
            else {
                if ( !$paramclass->isSubclassOf( $basetype ) ) {
                    throw New Exception( 'Type hint of parameter ' . $paramname . ' of type-safe function ' . $function . ' does not exist or is not derived from tBaseType' );
                }
                if ( isset( $req[ $paramname ] ) ) {
                    $params[] = $paramclass->newInstance( $req[ $paramname ] );
                }
                else {
                    $params[] = $paramclass->newInstance( false );
                }
            }
        }

        if ( isset( $method ) ) {
            return $func->invokeArgs( $obj, $params );
        }

        return call_user_func_array( $function , $params );
    }
?>
