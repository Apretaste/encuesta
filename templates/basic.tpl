{if $no_surveys}
	<p>Lo siento pero no tenemos ninguna encuesta para usted en este momento. Estamos trabajamos en agregar encuestas a nuestra lista, por favor vuelva a revisar en unos d&iacute;as. Gracias por estar pendiente a nuestras encuestas.</p>
{else}
	<h1>Encuestas activas</h1>
	<table width="100%">
		<tr>
			<th align="left">T&iacute;tulo</th>
			<th align="center">Vence</th>
			<th>Hecho</th>
			<th>Valor</th>
		</tr>
		{foreach item=item from=$surveys}
		<tr>
			<td>{link href="ENCUESTA {$item->survey}" caption = "{$item->title}"}</td>
			<td align="center">{$item->deadline|date_format:"%d/%m/%y"}</td>
			<td align="center">{$item->completion|string_format:"%.0f"}%</td>
			<td align="center">{$item->value|number_format:2}</td>
		</tr>
		{/foreach}
	</table>

	{space10}

	<p><small>
		Escoja una encuesta para comenzar.<br/>
		Todas las encuestas son personales y an&oacute;nimas.<br/>
		El <i>Valor</i> es la cantidad que ganar&aacute; cuando finalice la encuesta.
	</small></p>
{/if}