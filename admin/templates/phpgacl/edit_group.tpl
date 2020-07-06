{include file="phpgacl/header.tpl"}
    <style type="text/css">
    {literal}
      select {
        margin-top: 0px;
      }
      input.group-name, input.group-value {
        width: 99%;
      }
    {/literal}
    </style>
  </head>
  <body>
{include file="phpgacl/navigation.tpl"}
    <form method="post" name="edit_group" action="edit_group.php">
      <table class="table table-sm table-bordered">
        <tbody>
          <tr class="thead-dark">
            <th width="4%">ID</th>
            <th width="32%">Parent</th>
            <th width="32%">Name</th>
            <th width="32%">Value</th>
          </tr>
          <tr class="align-top">
            <td class="text-center">{$id|default:"N/A"}</td>
            <td>
                <select class="form-control form-control-sm" name="parent_id" tabindex="0" multiple="multiple">
                    {html_options options=$options_groups selected=$parent_id}
                </select>
            </td>
            <td>
                <input class="form-control form-control-sm" type="text" name="name" value="{$name}">
            </td>
            <td>
                <input class="form-control form-control-sm" type="text" name="value" value="{$value}">
            </td>
          </tr>
          <tr class="table-secondary text-center">
            <td colspan="4">
              <div class="btn-group">
                <button type="submit" class="btn btn-sm btn-primary" name="action" value="Submit"><i class="{#icon_submit#}"></i> Submit</button>
                <button type="reset" class="btn btn-sm btn-dark" value="Reset"><i class="{#icon_reset#}"></i> Reset</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    <input type="hidden" name="group_id" value="{$id}">
    <input type="hidden" name="group_type" value="{$group_type}">
    <input type="hidden" name="return_page" value="{$return_page}">
  </form>
{include file="phpgacl/footer.tpl"}
