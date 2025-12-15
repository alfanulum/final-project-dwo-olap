<%@ page session="true" contentType="text/html; charset=ISO-8859-1" %>
  <%@ taglib uri="http://www.tonbeller.com/jpivot" prefix="jp" %>
    <%@ taglib prefix="c" uri="http://java.sun.com/jstl/core" %>

      <jp:mondrianQuery id="query01" jdbcDriver="com.mysql.jdbc.Driver"
        jdbcUrl="jdbc:mysql://localhost/wh_adventureworks?user=root&password="
        catalogUri="/WEB-INF/queries/dwadventureworks.xml">

        select
        {
        [Measures].[Total Sales],
        [Measures].[Order Quantity],
        [Measures].[Average Unit Price]
        } ON COLUMNS,

        {
        ([Time].[All Time],
        [Product].[All Products],
        [Customer].[All Customers],
        [Geography].[All Regions],
        [Sales Person].[All Sales Persons])
        } ON ROWS

        from [Sales]

      </jp:mondrianQuery>

      <c:set var="title01" scope="session">
        Query AdventureWorks Data Warehouse using Mondrian OLAP
      </c:set>