<style>
    .top-buffer { margin-top:20px; }
</style>

<?php $cnt = count($tree->photos);?>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 col-lg-offset-2">
            <div id="carousel-example-generic" class="carousel slide" data-ride="carousel">
                <!-- Indicators -->
                @if ($cnt > 1)
                <ol class="carousel-indicators">
                    @for($i = 0; $i < $cnt; $i++)
                    <li data-target="#carousel-example-generic" data-slide-to="{{$i}}" <?php if($i == 0) { ?> class="active" <?php } ?>></li>
                    @endfor
                </ol>
                @endif

                <!-- Wrapper for slides -->
                <div class="carousel-inner" role="listbox">

                    <?php $n = 0; ?>
                    @foreach($tree->photos as $photo)

                    <div class="item <?php if ($n == 0) { ?>active <?php } ?>">

                        {{HTML::image('/images/'.$photo->id.'.jpg', 'Tree image', array('class' => 'responsive-img'));}}
                    </div>
                    <?php $n++; ?>
                    @endforeach
                </div>

                <!-- Controls -->
                @if ($cnt > 1)
                <a class="left carousel-control" href="#carousel-example-generic" role="button" data-slide="prev">
                    <span class="glyphicon glyphicon-chevron-left text-success" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </a>
                <a class="right carousel-control" href="#carousel-example-generic" role="button" data-slide="next">
                    <span class="glyphicon glyphicon-chevron-right text-success" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </a>
                @endif
            </div>
        </div>

    </div>

    <div class="row top-buffer">
        <div class="col-lg-3">
            <span><b>Created on </b><br>{{$tree->time_created}}</span>
        </div>
        <div class="col-lg-3">
            <span><b>Last updated on </b><br>{{$tree->time_updated}}</span>
        </div>
        <div class="col-lg-3">
            <span><b>Tree is <?php if($tree->missing == 0) echo "not" ?> missing</b></span>
        </div>
        <div class="col-lg-3">
            <span><b>GPS accuracy of the tree location is {{$tree->primaryLocation->gps_accuracy}} meters</b></span>
        </div>

    </div>
    <br>
</div>