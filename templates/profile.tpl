<h1>Edite su perfil</h1>

<p>Para poder contestar encuestas primero llene su perfil. El perfil nos ayuda a interpretar los resultados. Esta informaci&oacute;n nunca se compartir&aacute; con terceros.</p>

<table id="profile" width="100%" cellspacing="0">
	<!-- GENDER -->
	<tr>
		<td valign="middle"><small>Sexo</small></td>
		<td valign="middle"><b>{$person->gender|lower|capitalize}</b></td>
		<td align="right" valign="middle">{select options="{$options->gender}" selected="{$person->gender}"}</td>
	</tr>

	<!-- SEXUAL ORIENTATION -->
	<tr>
		<td valign="middle"><small>Orientaci&oacute;n sexual</small></td>
		<td valign="middle"><b>{$person->sexual_orientation|lower|capitalize}</b></td>
		<td align="right" valign="middle">{select options="{$options->sexual_orientation}" selected="{$person->sexual_orientation}"}</td>
	</tr>

	<!-- DAY OF BIRTH -->
	<tr>
		<td valign="middle"><small>Cumplea&ntilde;os</small></td>
		<td valign="middle"><b>{$person->date_of_birth|date_format:"%e/%m/%Y"}</b></td>
		<td align="right" valign="middle">{button size="small" color="grey" caption="Cambiar" href="PERFIL CUMPLEANOS" desc="d:Escriba su fecha de cumpleannos usando la notacion DD/MM/AAAA, por ejemplo 5/2/1980" popup="true"  wait="false"}</td>
	</tr>

	<!-- SKIN -->
	<tr>
		<td valign="middle"><small>Piel</small></td>
		<td valign="middle"><b>{$person->skin|lower|capitalize}</b></td>
		<td align="right" valign="middle">{select options="{$options->skin}" selected="{$person->skin}"}</td>
	</tr>

	<!-- MARITAL STATUS -->
	<tr>
		<td valign="middle"><small>Estado civil</small></td>
		<td valign="middle"><b>{$person->marital_status|lower|capitalize}</b></td>
		<td align="right" valign="middle">{select options="{$options->marital_status}" selected="{$person->marital_status}"}</td>
	</tr>

	<!-- HIGHEST SCHOOL LEVEL-->
	<tr>
		<td valign="middle"><small>Nivel escolar</small></td>
		<td valign="middle"><b>{$person->highest_school_level|lower|capitalize}</b></td>
		<td align="right" valign="middle">{select options="{$options->highest_school_level}" selected="{$person->highest_school_level}"}</td>
	</tr>

	<!-- OCCUPATION -->
	<tr>
		<td valign="middle"><small>Profesi&oacute;n</small></td>
		<td valign="middle"><b>{$person->occupation|lower|capitalize}</b></td>
		<td align="right" valign="middle">{select options="{$options->occupation}" selected="{$person->occupation}"}</td>
	</tr>

	<!-- PROVINCE-->
	<tr>
		<td valign="middle"><small>Provincia</small></td>
		<td valign="middle"><b>{$person->province|lower|capitalize}</b></td>
		<td align="right" valign="middle">{select options="{$options->province}" selected="{$person->province}"}</td>
	</tr>

	<!-- RELIGION -->
	<tr>
		<td valign="middle"><small>Religi&oacute;n</small></td>
		<td valign="middle"><b>{$person->religion|lower|capitalize}</b></td>
		<td align="right" valign="middle">{select options="{$options->religion}" selected="{$person->religion}"}</td>
	</tr>
</table>

{space15}

<center>
	{button href="ENCUESTA" caption="Continuar"}
</center>

<style>
	#profile tr {
		height: 40px;
	}
	#profile tr:nth-child(odd) {
		background-color: #F2F2F2;
	}
</style>
