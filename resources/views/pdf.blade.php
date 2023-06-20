<html>

<head>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,700');

        $base-spacing-unit: 24px;
        $half-spacing-unit: $base-spacing-unit / 2;

        $color-alpha: #1772FF;
        $color-form-highlight: #EEEEEE;

        *,
        *:before,
        *:after {
            box-sizing: border-box;
        }

        body {
            padding: $base-spacing-unit;
            font-family: 'Source Sans Pro', sans-serif;
            margin: 0;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 0;
        }

        .container {
            max-width: 1000px;
            margin-right: auto;
            margin-left: auto;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .table {
            width: 100%;
            border: 1px solid $color-form-highlight;
        }

        .table-header {
            display: flex;
            width: 100%;
            background: #000;
            padding: ($half-spacing-unit * 1.5) 0;
        }

        .table-row {
            display: flex;
            width: 100%;
            padding: ($half-spacing-unit * 1.5) 0;

            &:nth-of-type(odd) {
                background: $color-form-highlight;
            }
        }

        .table-data,
        .header__item {
            flex: 1 1 20%;
            text-align: center;
        }

        .header__item {
            text-transform: uppercase;
        }

        .filter__link {
            color: white;
            text-decoration: none;
            position: relative;
            display: inline-block;
            padding-left: $base-spacing-unit;
            padding-right: $base-spacing-unit;

            &::after {
                content: '';
                position: absolute;
                right: -($half-spacing-unit * 1.5);
                color: white;
                font-size: $half-spacing-unit;
                top: 50%;
                transform: translateY(-50%);
            }

            &.desc::after {
                content: '(desc)';
            }

            &.asc::after {
                content: '(asc)';
            }

        }
    </style>
    <script>
        var properties = [
            'name',
            'wins',
            'draws',
            'losses',
            'total',
        ];

        $.each(properties, function(i, val) {

            var orderClass = '';

            $("#" + val).click(function(e) {
                e.preventDefault();
                $('.filter__link.filter__link--active').not(this).removeClass('filter__link--active');
                $(this).toggleClass('filter__link--active');
                $('.filter__link').removeClass('asc desc');

                if (orderClass == 'desc' || orderClass == '') {
                    $(this).addClass('asc');
                    orderClass = 'asc';
                } else {
                    $(this).addClass('desc');
                    orderClass = 'desc';
                }

                var parent = $(this).closest('.header__item');
                var index = $(".header__item").index(parent);
                var $table = $('.table-content');
                var rows = $table.find('.table-row').get();
                var isSelected = $(this).hasClass('filter__link--active');
                var isNumber = $(this).hasClass('filter__link--number');

                rows.sort(function(a, b) {

                    var x = $(a).find('.table-data').eq(index).text();
                    var y = $(b).find('.table-data').eq(index).text();

                    if (isNumber == true) {

                        if (isSelected) {
                            return x - y;
                        } else {
                            return y - x;
                        }

                    } else {

                        if (isSelected) {
                            if (x < y) return -1;
                            if (x > y) return 1;
                            return 0;
                        } else {
                            if (x > y) return -1;
                            if (x < y) return 1;
                            return 0;
                        }
                    }
                });

                $.each(rows, function(index, row) {
                    $table.append(row);
                });

                return false;
            });

        });
    </script>
</head>

<body>
    <center>
        <h1>Table of User Data</h1>
    </center>
    <br><br>
    <table width="100%">
        <thead>
            <tr class="table-header">
                @foreach ($data['fields'] as $field)
                    <th class="header__item" style="color: white;">{{ $field }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data['results'] as $result)
                <tr class="table-row">
                    @foreach ($data['fields'] as $field)
                        <td class="table-data">{{ $result->$field }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
