/*
    Developer: dionyziz
*/

var Coala = {
	StoredObjects: [],
	ThreadedRequests: [],
	LazyCommit: null,
	Cold: function ( unitid , parameters ) {
		this._AppendRequest( unitid , parameters , 'cold' );
		this.LazyCommit = setTimeout( 'Coala.Commit()' , 50 );
	},
	Warm: function ( unitid , parameters ) {
		this._AppendRequest( unitid , parameters , 'warm' );
		this.LazyCommit = setTimeout( 'Coala.Commit()' , 50 );
	},
	_AppendRequest: function ( unitid , parameters , type ) {
		Coala.ThreadedRequests.push( 
			{ 
				'unitid' : unitid , 
				'parameters' : parameters , 
				'type' : type 
			} 
		);
	},
	Commit: function () {
		if ( Coala.ThreadedRequests.length == 0 ) {
			// nothing to commit
			return;
		}
		
		request = { 'ids' : '' };
		ids = [];
		warm = false;
		for ( i in Coala.ThreadedRequests ) {
			args = [];
			for ( j in Coala.ThreadedRequests[ i ].parameters ) {
                switch ( typeof( Coala.ThreadedRequests[ i ].parameters[ j ] ) ) {
                    case 'object': // object or array
                    case 'function': // function
    					Coala.StoredObjects[ Coala.StoredObjects.length ] = Coala.ThreadedRequests[ i ].parameters[ j ];
    					arg = 'Coala.StoredObjects[' + ( Coala.StoredObjects.length - 1 ) + ']';
                        break;
                    case 'boolean':
                        if ( Coala.ThreadedRequests[ i ].parameters[ j ] ) {
                            arg = 1;
                        }
                        else {
                            arg = 0;
                        }
                        break;
                    default: // scalar type
                        arg = Coala.ThreadedRequests[ i ].parameters[ j ];
                        break;
				}
				args.push( encodeURIComponent( j ) + '=' + encodeURIComponent( arg ) );
			}
			request[ 'p' + i ] = args.join( '&' );
			switch ( Coala.ThreadedRequests[ i ].type ) {
				case 'warm':
					symbol = '!';
					warm = true;
					break;
				case 'cold':
					symbol = '~';
					break;
				default:
					alert( 'Invalid coala call type' );
			}
			ids.push( symbol + Coala.ThreadedRequests[ i ].unitid );
		}
		if ( warm ) {
			method = 'post';
		}
		else {
			method = 'get';
		}
		request.ids = ids.join( ':' );
		this._PlaceRequest( request , method );
		Coala.ThreadedRequests = [];
	},
	_PlaceRequest: function ( request , method ) {
		if ( request == null ) {
			request = {};
		}
		Socket = this._AJAXSocket(); // instanciate new socket object
		if ( Socket == null ) {
			// this shouldn't happen; browser is not XMLHTTP-compatible
			return false;
		}
		realparameters = [];
		for ( parameter in request ) {
			realparameters.push( encodeURIComponent( parameter ) + '=' + encodeURIComponent( request[ parameter ] ) );
		}
		Socket.connect( "coala.php" , method , realparameters.join( '&' ) , this._Callback );
		return true; // successfully pushed request
	},
	_Callback: function ( xh ) {
		if ( xh.readyState != 4 ) {
			alert( "An XMLHTTP error has occured. Please try again later" );
			return;
		}
        if ( typeof water_debug_data != 'undefined' ) {
            old_water_debug_data = water_debug_data;
        }
        else {
            old_water_debug_data = {};
        }
        
		// execute unit
        resp = xh.responseText;
        
        if ( resp.substr( 0, 'while(1);'.length ) != 'while(1);' ) {
            alert( 'Invalid Coala initization string: \n' + resp );
            return;
        }
        
        resp = resp.substr( 'while(1);'.length ); // JS hijacking prevention
		eval( resp );
		if ( water_debug_data ) {
			coala_water_debug_data = water_debug_data; // could be used later, if water improves
			water_debug_data = old_water_debug_data;
		}
	},
	_AJAXSocket: function () {
		// internal class variables
		var xh; // contains a reference to our xmlhttp object
		var bComplete = false;
		
		// public class functions; callable from outside
		
		// main connect function, used to perform an XMLHTTP request
		this.connect = function( sURL , sMethod , sVars , fnDone ) {
			// if we don't have an xmlhttp object there's no point in requesting anything
			if ( !xh )
				// just return false
				return false;
			// okay, let's get started; operation hasn't been completed yet
			bComplete = false;
			// make sure the method is uppercase ("GET" or "POST")
			sMethod = sMethod.toUpperCase();
			
			// just to make sure no errors occur
			try {
				// if it's a GET method
				if ( sMethod == "GET" ) {
					// do a simple request
					xh.open( sMethod, sURL + "?" + sVars, true );
					sVars = "";
				}
				else {
					// do a request in the same manner
					xh.open( sMethod, sURL, true );
					// and add the variables to the HTTP header
					xh.setRequestHeader( "Method", "POST " + sURL + " HTTP/1.1" );
					xh.setRequestHeader( "Content-Type",
						"application/x-www-form-urlencoded" );
				}
				// use the onreadystatechange callback method of the xmlhttp object
				xh.onreadystatechange = function() {
					// if the xmlhttp request was successful and we think that the operation hasn't been completed...
					if ( !bComplete && xh.readyState == 4 ) {
						// mark the operation as completed
						bComplete = true;
						// and call the callback function passing the requests identifiers and the xmlhttp object required to get the downloaded results
						fnDone( xh );
					}
				};
				// okay, after we've set up everything, we can safely send the request
				xh.send(sVars);
			}
			catch( z ) { 
				// woops, something went wrong
				return false; 
			}
			// everything okay
			return true;
		};
		
		// constructor function begins here
		// try to create an XMLHTTP object instance
		// try/catch pairs to avoid errors
		try {
			// ActiveX, Msxml2.XMLHTTP
			xh = new ActiveXObject( "Msxml2.XMLHTTP" ); 
		}
		catch (e) { 
			try { 
				// ActiveX, Microsoft.XMLHTTP
				xh = new ActiveXObject( "Microsoft.XMLHTTP" ); 
			}
			catch (e) { 
				try { 
					// non-ActiveX, normal class (used by everyone apart from microsoft ._.)
					xh = new XMLHttpRequest(); 
				}
				catch (e) { 
					// last catch, exceptions everywhere, can't create XMLHTTP
					xh = false; 
				}
			}
		}
		
		// no xmlhttp object was created, the constructor should return null
		if ( !xh )
			return null;
		
		// return the newly created class
		return this;
	}
};
