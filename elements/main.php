<?php
    class ElementMain extends Element {
        public function Render() {
            global $page;
            
            // attach global scripts and stylesheets here
            // $page->AttachStylesheet( 'css/main.css' );
            // $page->AttachScript( 'js/main.js' );

            ob_start();
            $res = MasterElement();
            $master = ob_get_clean();
            
            // place element calls to headers here
            if ( $res === false ) { // If the page requested is not in the pages available by pagemap
                Element( '404' );
            }
            else {
                echo $master;
            }
            // place element calls to footers and tracking scripts here
            
            // pass
            return $res;
        }
    }
?>
