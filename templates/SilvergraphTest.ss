digraph g {
graph [
rankdir = "LR"
];
node [
fontsize = "16"
shape = "none"
margin = "0"
];
edge [
];
<% loop Classes %>
"$ClassName" [
label = <
        <table border="0" cellborder="1" cellspacing="0" cellpadding="4">
            <tr><td colspan="2" bgcolor="#cccccc"><font>$ClassName</font></td></tr>
            <% if FieldList %>
                <% loop FieldList %>
                    <tr>
                        <td>$FieldName</td>
                        <td>$DataType</td>
                    </tr>
                <% end_loop %>
            <% end_if %>
        </table>
        >
];
<% end_loop %>
<% loop Classes %>
    <% if HasOne %>
    <% loop HasOne %>
		<%-- special case for Parent, replace with "extends" --%>
		<% if Name == "Parent" %>
			"$Up.ClassName" -> "$RemoteClass"[label="extends"];
		<% else %>
			"$Up.ClassName" -> "$RemoteClass"[label="$Name (has_one)"];
		<% end_if %>
    <% end_loop %>
    <% end_if %>

    <% if HasMany %>
    <% loop HasMany %>
        "$Up.ClassName" -> "$RemoteClass"[label="$Name (has_many)"];
    <% end_loop %>
    <% end_if %>

    <% if ManyMany %>
    <% loop ManyMany %>
        "$Up.ClassName" -> "$RemoteClass"[label="$Name (many_many)"];
    <% end_loop %>
    <% end_if %>

<% end_loop %>
}