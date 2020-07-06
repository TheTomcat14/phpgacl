<div class="container">
{if $hidemenu neq TRUE}
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <a class="navbar-brand" href="#">phpGACL</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item{if $current eq 'aro_group'} active{/if}"><a class="nav-link" href="group_admin.php?group_type=aro">ARO Group Admin</a></li>
      <li class="nav-item{if $current eq 'axo_group'} active{/if}"><a class="nav-link" href="group_admin.php?group_type=axo">AXO Group Admin</a></li>
      <li class="nav-item{if $current eq 'acl_admin'} active{/if}"><a class="nav-link" href="acl_admin.php?return_page=acl_admin.php">ACL Admin</a></li>
      <li class="nav-item{if $current eq 'acl_list'} active{/if}"><a class="nav-link" href="acl_list.php?return_page=acl_list.php">ACL List</a></li>
      <li class="nav-item{if $current eq 'acl_test'} active{/if}"><a class="nav-link" href="acl_test.php">ACL Test</a></li>
      <li class="nav-item{if $current eq 'acl_debug'} active{/if}"><a class="nav-link" href="acl_debug.php">ACL Debug</a></li>
      <li class="nav-item{if $current eq 'about'} active{/if}"><a class="nav-link" href="about.php">About</a></li>
      <li class="nav-item"><a class="nav-link" href="../docs/manual.html" target="_blank">Manual</a></li>
      <li class="nav-item"><a class="nav-link" href="../docs/phpdoc/" >API Guide</a></li>
    </ul>
  </div>
</nav>
{/if}
<nav aria-label="breadcrumb" class="mt-1">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="#">phpGACL</a></li>
    <li class="breadcrumb-item active" aria-current="page">{$page_title}</li>
  </ol>
</nav>
