{include file="phpgacl/header.tpl"}
  </head>
  <body>
  {include file="phpgacl/navigation.tpl"}
    <div class="text-center">
      {if $first_run != 1}
      <strong>Help</strong>
      <p>
        Please join the <a href="https://sourceforge.net/mail/?group_id=57103">Mailing Lists</a> if you have
        any questions, comments, or support questions.
        <p><strong>TIP</strong>: Searching the Mailing List archives may be a good idea prior to emailing the list, <br>
        as well the below "Report" information may be helpful in any support questions.</p>
        <p>PLEASE DO NOT EMAIL ME DIRECTLY REGARDING SUPPORT QUESTIONS<br>
        You will receive answers faster on the mailing list, and any answers given may benefit others.</p>
        But if you must email me (Mike Benoit) directly, click <a href="mailto:ipso@snappymail.ca">here</a>.
      </p>
      <strong>Donate</strong>
      <p>
        Time working on phpGACL means less time that I can work to get paid.<br>
        Therefore any donations I receive will help me to devote more time to developing phpGACL.
        <p>However, I'd much rather donations in the form of code and/or documentation.</p>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="text-center">
        <input type="hidden" name="cmd" value="_xclick">
        <input type="hidden" name="business" value="ipso@snappymail.ca">
        <input type="hidden" name="item_name" value="php Generic Access Control List">
        <input type="hidden" name="no_note" value="1">
        <input type="hidden" name="currency_code" value="USD">
        <input type="hidden" name="tax" value="0">
        <input type="image" class="paypal" src="https://www.paypal.com/images/x-click-but04.gif" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
        </form>
      </p>
      {/if}
      {if $first_run != 1}
      <strong>Report</strong>
      {else}
      <strong class="text-danger">* Report *</strong>
      {/if}
      <p>
        Report some basic information back to the phpGACL project so we know where to spend our time.<br>
        <strong>All information will be kept private, will not be sold, and will only be used for informational purposes regarding phpGACL.</strong>
        <br><br>
      </p>
      <form method="post" name="about" action="about.php" class="text-center">
        <textarea class="form-control form-control-sm" name="system_information" rows="10" cols="60" wrap="VIRTUAL">{$system_info}</textarea>
        <br>
        <input type="hidden" name="system_info_md5" value="{$system_info_md5}">
        <input class="btn btn-sm btn-primary" type="submit" name="action" value="Submit">
      </form>
    {if $first_run != 1}
      <strong>Credits</strong>
<pre>
{$credits}
</pre>
        {/if}
    </div>
{include file="phpgacl/footer.tpl"}
