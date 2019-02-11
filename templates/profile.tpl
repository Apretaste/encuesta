<h1>Edite su perfil</h1>

<p>Para contestar encuestas primero complete su perfil. El perfil nos ayuda a interpretar los resultados. Su info nunca se comparte con terceros.</p>

<table id="profile" width="100%" cellspacing="0">
	<!-- GENDER -->
	<tr>
		<td valign="middle"><small>Sexo</small></td>
		<td valign="middle"><b id="value_sex">{$person->gender|lower|capitalize}</b></td>
		<td align="right" valign="middle">{button size="small" color="grey" caption="Cambiar" href="PERFIL SEXO" desc="m:Describa su genero [Masculino,Femenino]" popup="true" wait="false" callback="reloadSex"}</td>
	</tr>

	<!-- YEAR OF BIRTH -->
	<tr>
		<td valign="middle"><small>Cumplea&ntilde;os</small></td>
		<td valign="middle"><b id="value_birthday">{$person->year_of_birth}</b></td>
		<td align="right" valign="middle">{button size="small" color="grey" caption="Cambiar" href="PERFIL ANO" desc="m:Que a&ntilde;o usted nacio?[{$person->years}]*" popup="true"  wait="false" callback="reloadBirthday"}</td>
	</tr>

	<!-- SKIN -->
	<tr>
		<td valign="middle"><small>Piel</small></td>
		<td valign="middle"><b id="value_skin">{$person->skin|lower|capitalize}</b></td>
		<td align="right" valign="middle">{button size="small" color="grey" caption="Cambiar" href="PERFIL PIEL" desc="m:Describa su piel [Blanco,Negro,Mestizo,Otro]" popup="true"  wait="false" callback="reloadSkin"}</td>
	</tr>

	<!-- HIGHEST SCHOOL LEVEL-->
	<tr>
		<td valign="middle"><small>Nivel escolar</small></td>
		<td valign="middle"><b id="value_school">{$person->highest_school_level|lower|capitalize}</b></td>
		<td align="right" valign="middle">{button size="small" color="grey" caption="Cambiar" href="PERFIL NIVEL" desc="m:Cual es su nivel escolar? [Primario,Secundario,Tecnico,Universitario,Postgraduado,Doctorado,Otro]" popup="true"  wait="false" callback="reloadSchool"}</td>
	</tr>

	<!-- PROVINCE-->
	<tr id="container_province" class="{if $person->country != 'CU'}hidden{/if}">
		<td valign="middle"><small>Provincia</small></td>
		<td valign="middle"><b id="value_province">{$person->province|lower|capitalize}</b></td>
		<td align="right" valign="middle">{button size="small" color="grey" caption="Cambiar" href="PERFIL PROVINCIA" desc="m:En que provincia vive? [Pinar_del_Rio,La_Habana,Artemisa,Mayabeque,Matanzas,Villa_Clara,Cienfuegos,Sancti_Spiritus,Ciego_de_Avila,Camaguey,Las_Tunas,Holguin,Granma,Santiago_de_Cuba,Guantanamo,Isla_de_la_Juventud]" popup="true"  wait="false" callback="reloadProvince"}</td>
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

<script>
	function reloadSex(values) { document.getElementById('value_sex').innerHTML = values[0]; }
	function reloadBirthday(values) { document.getElementById('value_birthday').innerHTML = values[0]; }
	function reloadSkin(values) { document.getElementById('value_skin').innerHTML = values[0]; }
	function reloadSchool(values) { document.getElementById('value_school').innerHTML = values[0]; }
	function reloadProvince(values) { document.getElementById('value_province').innerHTML = values[0].replace("_", " "); }
</script>
