{include file="phpgacl/header.tpl"}
{include file="phpgacl/acl_admin_js.tpl"}
  </head>
  <body onload="document.object_search.name_search_str.focus();">
{include file="phpgacl/navigation.tpl" hidemenu="1"}
    <form method="get" name="object_search" action="object_search.php">
      <table class="table table-sm table-bordered">
        <tbody>
          <tr class="thead-dark">
            <th colspan="2">{$object_type_name} > {$section_value_name}</th>
          </tr>
          <tr>
            <td width="25%"><strong>Name:</strong></td>
            <td width="75%"><input type="text" class="form-control form-control-sm" name="name_search_str" value="{$name_search_str}"></td>
          </tr>
          <tr>
            <td><strong>Value:</strong></td>
            <td><input type="text" class="form-control form-control-sm" name="value_search_str" value="{$value_search_str}"></td>
          </tr>
          <tr class="table-secondary text-center">
            <td colspan="2">
              <div class="btn-group">
                <button type="submit" class="btn btn-sm btn-primary" name="action" value="Search"><i class="{#icon_search#}"></i> Search</button>
                <button type="button" class="btn btn-sm btn-danger" name="action" value="Close" onClick="window.close();"><i class="{#icon_close#}"></i> Close</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
{if (strlen($total_rows) != 0)}
    <br>
      <table class="table table-sm table-bordered">
        <tbody>
          <tr>
            <th colspan="2">{$total_rows} Objects Found</th>
          </tr>
    {if ($total_rows > 0)}
          <tr class="align-middle text-center">
            <td width="90%">
        <select name="objects" class="search" tabindex="0" size="10" multiple>
          {html_options options=$options_objects}
        </select>
            </td>
            <td width="10%">
        <input type="button" class="btn btn-sm btn-primary" name="select" value="&nbsp;&gt;&gt;&nbsp;" onClick="opener.select_item(opener.document.forms['{$src_form}'].elements['{$object_type}_section'], this.form.elements['objects'], opener.document.forms['{$src_form}'].elements['selected_{$object_type}[]']);">
             </td>
          </tr>
    {/if}
        </tbody>
      </table>
{/if}
  <input type="hidden" name="src_form" value="{$src_form}">
  <input type="hidden" name="object_type" value="{$object_type}">
  <input type="hidden" name="section_value" value="{$section_value}">
  </form>
{include file="phpgacl/footer.tpl"}
