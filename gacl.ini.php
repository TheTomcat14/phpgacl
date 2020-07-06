;<? if (; //Cause parse error to hide from prying eyes?>
;
; *WARNING*
;
; DO NOT PUT THIS FILE IN YOUR WEBROOT DIRECTORY.
;
; *WARNING*
;
; Anyone can view your database password if you do!
;
debug 			= FALSE

;
;Database
;
; db_type 		= "mysqli"
; db_host			= "localhost"
; db_user			= "root"
; db_password		= ""
; db_name			= "phpgacl"
; db_table_prefix		= ""
db_type 		= "mysqli"
db_host			= "localhost"
db_user			= "root"
db_password		= ""
; db_name			= "gdpr-tool-contabo-20191011"
db_name			= "phpgacl_test"
; db_table_prefix		= "phpgacl_"
db_table_prefix		= ""

;
;Caching
;
caching			    = FALSE
force_cache_expire	= TRUE
; cache_dir		    = "/tmp/phpgacl_cache"
cache_dir		    = "/htdocs/phpgacl/phpgacl_cache"
cache_expire_time	= 600

;
;Admin interface
;
items_per_page 		= 100
max_select_box_items 	= 100
max_search_return_items = 200

;NO Trailing slashes
smarty_template_dir  = "templates"
smarty_compile_dir 	 = "templates_c"
smarty_config_dir    = "templates/configs"

