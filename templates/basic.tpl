{if $no_surveys}
<h1>No tienes encuestas que responder</h1>
{else}
<h1>Encuestas activas</h1>
<table width="100%">
	<tr>
		<th align="left">Encuesta</th>
		<th align="center">Fecha de caducidad</th>
		<th>% Completado</th>
	</tr>
	<tr>
		<td colspan="3"><hr /></td>
	</tr>
	{foreach item=item from =$surveys}
	<tr>
		<td>{link href="ENCUESTA {$item->survey}" caption = "{$item->title}"}</td>
		<td align="center">{$item->deadline}</td>
		<td align="center">{$item->completion|string_format:"%.0f"}%</td>
	</tr>

	<tr>
		<td colspan="3"><hr /></td>
	</tr>
	{/foreach}
</table>
{/if}
