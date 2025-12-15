<%@ page session="true" contentType="text/html; charset=ISO-8859-1" %>
  <%@ taglib uri="http://www.tonbeller.com/jpivot" prefix="jp" %>
    <%@ taglib prefix="c" uri="http://java.sun.com/jstl/core" %>

      <jp:mondrianQuery id="query01" jdbcDriver="com.mysql.jdbc.Driver"
        jdbcUrl="jdbc:mysql://localhost/wh_adventureworks?user=root&password="
        catalogUri="/WEB-INF/queries/dwoadw1.xml">

        select {[Measures].[Total Amount]} ON COLUMNS,
        {([Store].[All Stores],[Time].[All Times],[Customer].[All Customers])} ON ROWS
        from [PenjualanFilm]

      </jp:mondrianQuery>

      <c:set var="title01" scope="session">Query DWOADW1 using Mondrian OLAP</c:set>