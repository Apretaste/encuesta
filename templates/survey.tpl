<big><big><b>{$survey->title}</b></big></big>

{if $survey->details neq ""}
	<p><small>{$survey->details}</small></p>
{/if}

{if $survey->completed}
	<table width="100%" cellspacing="0" cellpadding="15">
		<tr>
			<td bgcolor="yellow" align="center">
				<b>Usted ya respondi&oacute; esta encuesta, y como agradecimiento se le agregaron §{$survey->value|number_format:2} a su saldo. Muchas gracias por su participaci&oacute;n.</b>
			</td>
		</tr>
	</table>
{else}
	<p><small><font color="red">Responda las preguntas y presione el bot&oacute;n "Validar" para completar la encuesta y ganar §{$survey->value|number_format:2}.</font></small></p>
{/if}

{space5}

<table width="100%" cellspacing="0">
{foreach from=$survey->questions item=question}
<tr {if $question@iteration is even}bgcolor="#F2F2F2"{/if}>
	<td valign="top" width="1">{space5}{$question@iteration})</td>
	<td>
		{space5}
		<b>{$question->title}</b>
		{if $question->completed}<font color="green">&#10004;</font>{/if}
		{space5}
		{foreach item=answer from=$question->answers}
			{if $question->completed}
				{if $answer->choosen}<small>{$answer->title}</small>{/if}
			{else}
				<li>{link href="ENCUESTA RESPONDER {$answer->id}" caption="{$answer->title}" wait="false"}</li>
			{/if}
		{/foreach}
		{space5}
	</td>
</tr>
{/foreach}
</table>

{space15}

<center>
	{if not $survey->completed}
		<p><small><font color="red">Presione el bot&oacute;n "Validar" cuando responda todas las preguntas.</font></small></p>
		{button href="ENCUESTA {$survey->id}" caption="Validar"}
	{/if}
	{button href="ENCUESTA" caption="Atr&aacute;s" color="grey"}
</center>
