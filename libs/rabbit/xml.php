<?php
/*
* Copyright (c) 2007, Dionysis Zindros <dionyziz@gmail.com>
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the <organization> nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY Dionysis Zindros ``AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL <copyright holder> BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class XMLException extends Exception {
}

class XMLNode {
    public $attributes; // array key => value
    public $childNodes; // array of XMLNodes or strings
    public $parentNode;
    public $nodeName;
    
    public function XMLNode( $name ) {
        $this->nodeName = $name;
        $this->parentNode = false;
        $this->childNodes = array();
        $this->attributes = array();
    }
    public function appendChild( $child ) {
        w_assert( is_string( $child ) || $child instanceof XMLNode );
        if ( is_string( $child ) ) {
            $lastchild = count( $this->childNodes ) - 1;
            if ( $lastchild >= 0 && is_string( $this->childNodes[ $lastchild ] ) ) {
                $this->childNodes[ $lastchild ] .= $child;
                return;
            }
        }
        $this->childNodes[] = $child;
    }
    public function firstChild() {
        if ( count( $this->childNodes ) ) {
            return $this->childNodes[ 0 ];
        }
        return false;
    }
    public function lastChild() {
        if ( count( $this->childNodes ) ) {
            return $this->childNodes[ count( $this->childNodes ) - 1 ];
        }
        return false;
    }
    public function getElementsByTagName( $name ) { // only direct children! (unlike DOM)
        $ret = array();
        foreach ( $this->childNodes as $i => $child ) {
            if ( $child->nodeName == $name ) {
                $ret[] = $this->childNodes[ $i ];
            }
        }
        return $ret;
    }
    public function setAttribute( $name, $value ) {
        $this->attributes[ $name ] = $value;
    }
    public function attribute( $name ) {
        if ( isset( $this->attributes[ $name ] ) ) {
            return $this->attributes[ $name ];
        }
        return false;
    }
    public function innerHTML() {
        $ret = '';
        foreach ( $this->childNodes as $xmlnode ) {
            if ( is_string( $xmlnode ) ) {
                $ret .= htmlspecialchars( $xmlnode );
            }
            else {
                $ret .= $xmlnode->outerHTML();
            }
        }
        return $ret;
    }
    public function outerHTML() {
        $ret = '<' . $this->nodeName;
        
        $attributes = array();
        foreach ( $this->attributes as $attribute => $value ) {
            $attributes[] = $attribute . '="' . htmlentities( $value, ENT_QUOTES, 'UTF-8' ) . '"';
        }
        
        if ( !empty( $attributes ) ) {
            $ret .= ' ' . implode( ' ', $attributes );
        }
        
        if ( empty( $this->childNodes ) ) {
            $ret .= '/>';
        }
        else {
            $ret .= '>';
            $ret .= $this->innerHTML();
            $ret .= '</' . $this->nodeName . '>';
        }
        
        return $ret;
    }
}

class XMLParser {
    private $mDepth;
    private $mNodesQueue; /* array of XMLNode/string */
    private $mLastNode;
    private $mXML;
    private $mError;
    private $mNativeParser;
    private $mIgnoreEmptyTextNodes;
    
    public function ignoreEmptyTextNodes( $preference ) {
        w_assert( is_bool( $preference ) );
        $this->mIgnoreEmptyTextNodes = $preference;
    }
    public function parseElementStart( $parser, $name, $attribs ) {
        $newnode = New XMLNode( $name );
        foreach ( $attribs as $attribute => $value ) {
            $newnode->setAttribute( $attribute, $value );
        }
        if ( count( $this->mNodesQueue ) ) {
            $current = $this->mNodesQueue[ count( $this->mNodesQueue ) - 1 ];
            $newnode->parentNode = $current;
            $current->appendChild( $newnode );
        }
        $this->mNodesQueue[] = $newnode; // push
    }
    public function parseElementEnd( $parser, $name ) {
        $this->mLastNode = array_pop( $this->mNodesQueue );
    }
    public function parseText( $parser, $string ) {
        if ( $this->mIgnoreEmptyTextNodes && trim( $string ) == '' ) {
            return;
        }
        if ( !count( $this->mNodesQueue ) ) {
            $this->mError = 'Text node cannot be root node';
        }
        $current = $this->mNodesQueue[ count( $this->mNodesQueue ) - 1 ];
        $current->appendChild( $string );
    }
    public function __construct( $xml ) {
        $this->mXML = $xml;
        $this->mNodesQueue = array();
        $this->mError = false;
        $this->mLastNode = false;
        $this->mIgnoreEmptyTextNodes = true;
    }
    public function Parse() {
        global $water;
        
        $this->mNativeParser = xml_parser_create( 'UTF-8' );
        xml_parser_set_option(
            $this->mNativeParser,
            XML_OPTION_CASE_FOLDING,
            0
        );
        xml_parser_set_option(
            $this->mNativeParser,
            XML_OPTION_SKIP_WHITE,
            0
        );
        xml_set_element_handler(
            $this->mNativeParser, 
            array( $this, 'parseElementStart' ),
            array( $this, 'parseElementEnd' )
        );
        xml_set_character_data_handler(
            $this->mNativeParser,
            array( $this, 'parseText' )
        );
        $success = xml_parse( $this->mNativeParser, $this->mXML );
        if ( !$success ) {
            $this->mError = xml_error_string(
                                xml_get_error_code( $this->mNativeParser )
                            ) 
                            . ' at line ' 
                            . xml_get_current_line_number(
                                $this->mNativeParser
                            );
        }
        xml_parser_free( $this->mNativeParser );
        
        if ( !empty( $this->mError ) ) {
            throw New Exception( 'XML Parsing Failed: ' . $this->mError );
        }
        if ( $this->mLastNode === false ) {
            throw New Exception( 'XML Parsing Failed: No root node specified (' . $this->mXML . ')' );
        }
        $water->Trace( 'Parsed XML', $this->mXML );
        
        return $this->mLastNode; // return root node (or false if none)
    }
}

?>
