{if empty($surveys)}
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
			<td align="center">§{$item->value|number_format:2}</td>
		</tr>
		{/foreach}
	</table>

	{space10}

	<p><small>
		Todas las encuestas son an&oacute;nimas. Nadie leer&aacute; tu selecci&oacute;n. <br/>
		El <i>Valor</i> son los cr&eacute;ditos que ganar&aacute; al completar la encuesta.
	</small></p>
{/if}

{if not empty($finished)}
	{space10}
	<hr/>
	{space10}

	<b>Encuestas completadas</b>
	<ul>
	{foreach item=item from=$finished}
		<li>{$item->title}</li>
	{/foreach}
	</ul>
{/if}
