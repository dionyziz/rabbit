<?php
    class Element404 extends Element {
        public function Render() {
            header( 'HTTP/1.0 404 Not Found' );
            ?><p>We're sorry, but the page you requested could not be found.</p><?php
        }
    }
?>
