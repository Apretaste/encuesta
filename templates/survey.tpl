<h1>{$survey->title}</h1>
{foreach item=question from=$survey->questions}
<h3>{$question->title}</h3>
{if $credit_plus > 0}
<p>Haz completado esta encuesta, por lo que te hemos regalado {$credit_plus} de cr&eacute;dito personal.</p>
{/if}
	<ul>
	{foreach item=answer from=$question->answers}
		<li>{if $question->selectable == false}
				{if $answer->choosen}
				<strong>{$answer->title}</strong>
				{else}
				{$answer->title}
				{/if}
			{else}
				{link href="ENCUESTA RESPONDER {$answer->id}" caption="{$answer->title}"}
			{/if}
		</li>
	{/foreach}
	</ul>
{/foreach}