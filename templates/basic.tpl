{if $no_surveys}
	<h1>No tienes encuestas que responder</h1>
{else}
	<h1>Encuestas activas</h1>
	{foreach item=item from =$surveys}
		<p>{link href="ENCUESTA {$item->survey}" caption = "{$item->title}"}</p>
	{/foreach}
{/if}