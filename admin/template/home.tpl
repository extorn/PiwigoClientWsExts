{combine_css path=$PWG_CLI_EXT_PATH|@cat:"admin/template/style.css"}

{html_style}
  h4 {
    text-align:left !important;
  }
{/html_style}

<div class="titlePage">
	<h2>PiwigoClientWsExts</h2>
</div>

<form method="post" action="" class="properties">
<fieldset>
  <legend>{'What PiwigoClientWsExts can do for me?'|translate}</legend>

  {$INTRO_CONTENT}
</fieldset>

</form>