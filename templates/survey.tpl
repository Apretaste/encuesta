<table width="100%">
	<tr>
		<td><big><big><b>{$survey->title}</b></big></big><br/></td>
		<td align="right" valign="top">
			{button href="ENCUESTA {$survey->id}" caption="RECARGAR" color="grey" size="small"}
		</td>
	</tr>
	{if $survey->details neq ""}
	<tr>
		<td colspan="2"><p><small>{$survey->details}</small></p></td>
	</tr>
	{/if}
</table>

{space10}

<table width="100%" cellspacing="0">
{foreach from=$survey->questions item=question}
<tr {if $question@iteration is even}bgcolor="#F2F2F2"{/if}>
	<td valign="top" width="1">{space5}{$question@iteration})</td>
	<td>
		{space5}
		<b>{$question->title}</b>
		{if not $question->selectable}<font color="green">&#10004;</font>{/if}
		{space5}
		{foreach item=answer from=$question->answers}
			{if not $question->selectable} 
				{if $answer->choosen}<small>{$answer->title}</small>{/if} 
			{else}
				<li>{link href="ENCUESTA RESPONDER {$answer->id}" caption="{$answer->title}" size="small"}</li> 
			{/if}
		{/foreach}
		{space5}
	</td>
</tr>
{/foreach}
</table>

{space15}

<center>
	<p><small><font color="red">Si respondi&oacute; todas las preguntas, pronto le llegar&aacute; un email de confirmaci&oacute;n. &iexcl;Gracias!</font></small></p>
	{space5}
	{button href="ENCUESTA" caption="Encuestas"}
	{button href="ENCUESTA {$survey->id}" caption="Recargar" color="grey"}
</center>