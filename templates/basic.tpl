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
		{foreach item=item from=$surveys name=loop}
			<tr>
				<td>{link href="ENCUESTA {$item->survey}" caption = "{$item->title}"}</td>
				<td align="center">{$item->deadline|date_format:"%d/%m/%y"}</td>
				<td align="center">{$item->completion|string_format:"%.0f"}%</td>
				<td align="center">§{$item->value|number_format:2}</td>
			</tr>
			{if not $smarty.foreach.loop.last}
				<tr><td colspan="4"><hr/></td></tr>
			{/if}
		{/foreach}
	</table>

	{space10}

	<p><small>Las encuestas son an&oacute;nimas y seguras. Nadie leer&aacute; tu selecci&oacute;n. El <i>Valor</i> son los cr&eacute;ditos que ganar&aacute; al completar la encuesta.</small></p>
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

<style type="text/css">
	hr{
		border: 0;
		height: 0;
		border-top: 1px solid rgba(0, 0, 0, 0.1);
		border-bottom: 1px solid rgba(255, 255, 255, 0.3);
	}
</style>