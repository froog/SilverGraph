digraph g {
graph [
rankdir = "$Rankdir"
];
node [
fontsize = "16"
shape = "none"
margin = "0"
];
edge [
];
<% loop Folders %>
    <% if Group %>subgraph cluster_$Name {<% end_if %>
        color=blue;
        label = "$Name";
    <% loop Classes %>
        "$ClassName" [
        label = <
                <table border="0" cellborder="1" cellspacing="0" cellpadding="4">
                    <tr><td colspan="2" bgcolor="#d4d6d8"><b><font color="#0073c1" face="Helvetica Bold">$TableName</font></b></td></tr>
                    <% if FieldList %>
                        <% loop FieldList %>
                            <tr>
                                <td><font face="Helvetica Bold">$FieldName</font></td>
                                <td><font face="Helvetica Italic">$DataType</font></td>
                            </tr>
                        <% end_loop %>
                    <% end_if %>
                </table>
                >
        ];
        <% loop ManyManyList %>
            <% if ExtraFields %>
		        "$Name" [
		        label = <
		        <table border="0" cellborder="1" cellspacing="0" cellpadding="4">
			        <tr><td colspan="2" border="0">$Name (many_many)</td></tr>
                    <% loop ExtraFields %>
				        <tr>
					        <td><font face="Helvetica Bold">$FieldName</font></td>
					        <td><font face="Helvetica Italic">$DataType</font></td>
				        </tr>
                    <% end_loop %>
		        </table>
		        >
		        ];
            <% end_if %>
        <% end_loop %>
    <% end_loop %>
    <% if Group %>}<% end_if %>
<% end_loop %>

<% loop $Folders %>
    <% loop $Classes %>
        <% if $HasOneList %>
        <% loop $HasOneList %>
            <%-- special case for Parent, replace with "extends" --%>
            <% if $Name == "Parent" %>
                "$Up.ClassName" -> "$RemoteClass"[label="extends" style="dotted"];
            <% else %>
                "$Up.ClassName" -> "$RemoteClass"[label="$Name (has_one)"];
            <% end_if %>
        <% end_loop %>
        <% end_if %>

        <% if $HasManyList %>
        <% loop $HasManyList %>
            "$Up.ClassName" -> "$RemoteClass"[label="$Name (has_many)"];
        <% end_loop %>
        <% end_if %>

        <% if $ManyManyList %>
        <% loop $ManyManyList %>
            <% if $ExtraFields %>
	            "$Name" -> "$RemoteClass";
	            "$Name" -> "$Up.ClassName";
            <% else %>
                "$Up.ClassName" -> "$RemoteClass"[label="$Name (many_many)" dir=both];
            <% end_if %>
        <% end_loop %>
        <% end_if %>
    <% end_loop %>
<% end_loop %>
}
