<h1>Encuestas activas</h1>

{if $no_surveys}
	<p>Lo siento pero no tenemos ninguna encuesta para usted en este momento. Estamos trabajamos en agregar encuestas a nuestra lista, por favor vuelva a revisar en unos d&iacute;as. Gracias por estar pendiente a nuestras encuentas.</p>
{else}
	<table width="100%">
		<tr>
			<th align="left">Encuesta</th>
			<th align="center">Fecha de caducidad</th>
			<th>% Completado</th>
		</tr>
		<tr>
			<td colspan="3"><hr /></td>
		</tr>
		{foreach item=item from=$surveys}
		<tr>
			<td>{link href="ENCUESTA {$item->survey}" caption = "{$item->title}"}</td>
			<td align="center">{$item->deadline}</td>
			<td align="center">{$item->completion|string_format:"%.2f"}%</td>
		</tr>
	
		<tr>
			<td colspan="3"><hr /></td>
		</tr>
		{/foreach}
	</table>
{/if}