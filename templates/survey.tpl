<table width="100%">
	<tr>
		<td><h1>{$survey->title}</h1></td>
		<td align="right" valign="top">
			{button href="ENCUESTA {$survey->id}" caption="RECARGAR" color="grey" size="small"}
		</td>
	</tr>
</table>

{if $credit_plus > 0}
	<p>Haz completado esta encuesta, por lo que te hemos regalado {$credit_plus} de cr&eacute;dito personal.</p>
{/if} 

{foreach item=question from=$survey->questions}
	<h3>{$question->title}</h3>
	<ul>
		{foreach item=answer from=$question->answers}
			<li>
			{if not $question->selectable} 
				{if $answer->choosen} 
					<u><strong>{$answer->title}</strong></u> 
				{else} 
					<small>{$answer->title}</small> 
				{/if} 
			{else} 
				{link href="ENCUESTA RESPONDER {$answer->id}" caption="{$answer->title}"} 
			{/if}
			</li> 
		{/foreach}
	</ul>
{/foreach}

{space15}

<center>
	{button href="ENCUESTA" caption="Encuestas"}
	{button href="ENCUESTA {$survey->id}" caption="Recargar" color="grey"}
</center>