<h1>{$survey->title}</h1>
{button href="ENCUESTA {$survey->id}" caption="RECARGAR" color="blue" size="small"}
{if $credit_plus > 0}
<p>Haz completado esta encuesta, por lo que te hemos regalado {$credit_plus} de cr&eacute;dito personal.</p>
{/if} {foreach item=question from=$survey->questions}
<h3>{$question->title}</h3>
<ul>
	{foreach item=answer from=$question->answers}
	<li>
	{if $question->selectable == false} 
		{if $answer->choosen} 
			<u><strong>{$answer->title}</strong></u> 
		{else} 
			<small>{$answer->title}</small> 
		{/if} 
	{else} 
		{link href="ENCUESTA RESPONDER {$answer->id}" caption="{$answer->title}"} 
	{/if}
	</li> {/foreach}
</ul>
{/foreach}
<center>{button href="ENCUESTA" caption="ENCUESTAS"}</center>