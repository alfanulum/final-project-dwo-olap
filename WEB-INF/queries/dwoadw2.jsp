<%@ page session="true" contentType="text/html; charset=ISO-8859-1" %>
  <%@ taglib uri="http://www.tonbeller.com/jpivot" prefix="jp" %>
    <%@ taglib prefix="c" uri="http://java.sun.com/jstl/core" %>

      <jp:mondrianQuery id="query01" jdbcDriver="com.mysql.cj.jdbc.Driver"
        jdbcUrl="jdbc:mysql://localhost:3306/wh_adventureworks?user=root&password="
        catalogUri="/WEB-INF/queries/dwoadw2.xml">

        select
        {[Measures].[Jumlah Film], [Measures].[Total Pendapatan]} ON COLUMNS,
        CrossJoin([Film].[Kategori].Members, [Customer].[Nama Customer].Members) ON ROWS
        from [AnalisisFilm]

      </jp:mondrianQuery>

      <c:set var="title01" scope="session">
        Query DWOADW2 – Analisis Film dan Pelanggan
      </c:set>