<h1>Encuestas activas</h1>

{if $no_surveys}
	<p>Lo siento pero no tenemos ninguna encuesta para usted en este momento. Estamos trabajamos en agregar encuestas a nuestra lista, por favor vuelva a revisar en unos d&iacute;as. Gracias por estar pendiente a nuestras encuestas.</p>
{else}
	<table width="100%">
		<tr>
			<th align="left">Encuesta</th>
			<th align="center">Vence</th>
			<th>Hecho</th>
			<th>Valor</th>
		</tr>
		<tr>
			<td colspan="4"><hr/></td>
		</tr>
		{foreach item=item from=$surveys}
		<tr>
			<td>{link href="ENCUESTA {$item->survey}" caption = "{$item->title}"}</td>
			<td align="center">{$item->deadline|date_format:"%d/%m/%y"}</td>
			<td align="center">{$item->completion|string_format:"%.0f"}%</td>
			<td align="center">$5</td>
		</tr>
		{/foreach}
	</table>
	{space15}
{/if}
